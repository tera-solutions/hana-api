<?php

namespace App\Module\Portal\Controllers\Chat;

use App\Helpers\Task;
use App\Helpers\Util;
use App\Http\Controllers\Controller;
use App\Module\Portal\Model\Chat\Chat;
use App\Module\Portal\Model\Chat\GroupChat;
use App\Module\Portal\Model\Chat\GroupUser;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondSuccess([]);
            }

            $user_id = Auth::guard('api')->user()->id;

            if (!$request->group_code) {
                return $this->respondSuccess([]);
            }

            $chat = Chat::with(["parent", "user:id,full_name,avatar", 'media'])
                ->leftJoin("group_chats", "group_chats.code", "chats.group_code")
                ->leftJoin('group_users', function ($leftJoin) use ($user_id) {
                    $leftJoin
                        ->on('group_users.user_id', '=', DB::raw($user_id))
                        ->on('group_users.group_id', '=', 'group_chats.id');
                })
                ->where("chats.group_code", $request->group_code)
                ->where("chats.status", "<>", "block")
                // ->where("chats.created_at", ">", "group_users.created_at")
                ->select(
                    'chats.*',
                    'group_users.is_block',
                    'group_users.created_at as join_date'
                );

            if (isset($request->keyword) && $request->keyword) {
                $query = "chats.message LIKE '%" . $request->keyword . "%'";

                $chat->whereRaw($query);
            }

            if (isset($request->type) && $request->type) {
                $chat->where("chats.type", $request->type);
            }

            $parent_id = request()->get('parent_id', null);
            if (!empty($parent_id)) {
                $chat->where('chats.parent_id', $parent_id)
                    ->orWhere('chats.id', $parent_id);
            }

            $sort_field = "chats.created_at";
            $sort_des = "desc";

            if (isset($request->order_field) && $request->order_field) {
                $sort_field = $request->order_field;
            }

            if (isset($request->order_by) && $request->order_by) {
                $sort_des = $request->order_by;
            }

            $chat->orderBy($sort_field, $sort_des);

            $data = $chat->paginate($request->limit);

            return $this->respondSuccess($data);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    private function createChat($data)
    {
        $user_id = Auth::guard('api')->user()->id;
        $type = isset($data->type) ? $data->type : null;
        $message = isset($data->message) ? $data->message : null;
        $receive_id = isset($data->receive_id) ? $data->receive_id : null;
        $room_id = isset($data->room_id) ? $data->room_id : null;
        $media_id = isset($data->media_id) ? $data->media_id : null;
        $group_code = isset($data->group_code) ? $data->group_code : null;;

        if (empty($group_code)) {
            if (isset($type) && $type === "group") {
                $group_code = $room_id;
            }

            if (isset($type) && $type === "user") {
                $dataRoom = Chat::whereIn("created_by", [$user_id, $receive_id])
                    ->first();

                if ($dataRoom) {
                    $group_code = $dataRoom->group_code;
                }
            }

            if (empty($group_code)) {
                $checkUser = GroupUser::where("user_id", $user_id)
                    ->leftJoin("group_chats", "group_chats.id", "group_users.group_id")
                    ->select([
                        "group_users.*",
                        "group_chats.code as group_code"
                    ])
                    ->first();
                if ($checkUser) {
                    $group_code = $checkUser->group_code;
                }
            }
        }

        $group = GroupChat::where("code", $group_code)->first();

        $checkGroup = GroupChat::where("created_by", $receive_id)->first();

        if ($checkGroup && empty($group)) {
            $group = $checkGroup;
        }


        if (!$group) {
            $ref_count = Task::setAndGetReferenceCount("group_chat");
            $ref_no = Task::generateReferenceNumber("group_chat", $ref_count);

            $dataGroup = [
                'created_by' => $receive_id,
                'code' => $ref_no,
                'type' => "user",
                'created_at' => now(),
                'updated_at' => now()
            ];

            $group = GroupChat::create($dataGroup);

            if (empty($group)) {
                throw new \Exception("Lỗi trong quá trình gửi tin nhắn");
            }

            $dataGroupUser = [
                [
                    'group_id' => $group->id,
                    'user_id' => $receive_id,
                    'type' => "user",
                    'created_at' => now()
                ]
            ];

            $groupUser = GroupUser::insert($dataGroupUser);

            if (empty($groupUser)) {
                throw new \Exception("Lỗi trong quá trình gửi tin nhắn");
            }
        }

        $checkBlock = GroupUser::where("user_id", $user_id)
            ->where("group_id", $group->id)
            ->first();

        $status = "new";

        if ($checkBlock && $checkBlock->is_block == 1) {
            $status = "block";
        }


        $dataChat = [
            'created_by' => $user_id,
            'message' => $message,
            'media_id' => $media_id,
            'type' => $type,
            'status' => $status,
            'group_code' => $group->code,
            'created_at' => now()
        ];

        if ($group->type === "user") {
            $dataChat["receive_id"] = $receive_id;
        }

        $chat = Chat::create($dataChat);

        if ($checkBlock) {
            $checkBlock->count = $checkBlock->count  + 1;
            $checkBlock->save();
        }

        $group->updated_at = now();
        $group->save();

        if (!$chat) {
            throw new \Exception("Lỗi trong quá trình gửi tin nhắn");
        }

        return $chat;
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function sendMessage(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            if (empty($request->message)) {
                return $this->respondWithError("Vui lòng nhập nội dung", [], 500);
            }


            $user_id = Auth::guard('api')->user()->id;
            $receive_id = !empty($request->user_id) ? $request->user_id : 1;
            $room_id = $request->room_id;
            $type = $request->type;
            $message = $request->message;
            $group_code = $request->group_code;

            $dataChat = (object)[
                'user_id' => $user_id,
                'receive_id' => $receive_id,
                'room_id' => $room_id,
                'group_code' => $group_code,
                'message' => $message,
                'type' => $type,
            ];

            $chat = $this->createChat($dataChat);
            DB::commit();
            $message = "Đã gửi tin nhắn thành công";

            return $this->respondSuccess($chat, $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    public function sendImage(Request $request)
    {
        DB::beginTransaction();
        $resultFile = "";

        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            $user_id = Auth::guard('api')->user()->id;
            $username = Auth::guard('api')->user()->username;

            $device_code = $request->header("device-code");

            if (!$device_code) {
                return $this->respondWithError("Thiết bị bị từ chối!", [], 500);
            }

            $input = $request->only([
                'app_id',
                'upload_time',
                'secure_code',
                'file',
                'title',
                'description'
            ]);


            $request->validate([
                'file' => 'required|mimes:jpg,jpge,png,gif,gifv,pdf,doc,docx,xlsx,xltx,zip,csv,txt,avi,mov,webm,mp4,m4p,mpg,mp2,mpeg,mpe,mpv,m4v,svi,apk,psd,ai,sql,rar',
                'app_id' => 'required',
                'secure_code' => 'required',
            ]);

            if ($request->app_id != 2) {
                DB::rollBack();
                $message = "Tải file lên không thành công! Thiết bị bị từ chối";

                return $this->respondWithError($message, [], 500);
            }

            $file = $request->file('file');

            $title = null;

            if (!empty($request->title)) {
                $title = $request->title;
            }

            $folder = "";

            if (!empty($request->folder)) {
                $folder = $request->folder;
            }


            if (!$file) {
                DB::rollBack();
                $message = "Tải file lên không thành công! Thiết bị bị từ chối";

                return $this->respondWithError($message, [], 500);
            }

            $resultFile = Util::UploadResource($file, $title, $folder);

            $res = [
                "message" => "Tải file lên hệ thống thành công"
            ];

            if (isset($resultFile['path']) && $resultFile['path']) {
                $res['image'] = url($resultFile['path']);
            }

            if (isset($resultFile['thumb']) && $resultFile['thumb']) {
                $res['thumb'] = url($resultFile['thumb']);
            }
            $type = !empty($request->object_key) ? $request->object_key : "attachment";

            $dataUpdate = [
                'file_path' => $resultFile['path'],
                'file_name' =>  $resultFile['file_name'],
                'file_type' =>  $resultFile['type'],
                'file_size' =>  $resultFile['size'],
                'object_id' => !empty($request->object_id) ? $request->object_id : "",
                'object_type' => $type,
                'title' => $request->title,
                'description' => $request->description,
                'uploaded_by' => $user_id,
                'type' => $request->type,
            ];

            $result = Media::create($dataUpdate);

            if (!$result) {
                File::delete(public_path($resultFile['path']));
                return $this->respondWithError("Lỗi trong quá trình tải file", [], 500);
            }

            $user_id = Auth::guard('api')->user()->id;
            $receive_id = !empty($request->user_id) ? $request->user_id : 1;
            $room_id = $request->room_id;
            $type = $request->type;
            $message = $request->message;
            $group_code = $request->group_code;

            $dataChat = (object)[
                'user_id' => $user_id,
                'receive_id' => $receive_id,
                'room_id' => $room_id,
                'group_code' => $group_code,
                'media_id' => $result->id,
                'type' => $type,
            ];

            $data = $this->createChat($dataChat);

            $res["id"] = $result->id;
            $res["group_code"] = $data->group_code;

            DB::commit();
            return $this->respondSuccess($res);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            if (isset($resultFile['path']) &&  File::exists(public_path($resultFile['path']))) {
                File::delete(public_path($resultFile['path']));
            }
            return $this->respondWithError($message, [], 500);
        }
    }

    public function readMessage(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            if (empty($request->group_id)) {
                return $this->respondWithError("Vui lòng nhập nội dung", [], 500);
            }


            $user_id = Auth::guard('api')->user()->id;

            $checkUserGroup = GroupUser::where("group_id", $request->group_id)
                ->first();

            if ($checkUserGroup) {
                $checkUserGroup->count = 0;
                $checkUserGroup->save();
            } else {
                return $this->respondWithError("Không tìm thấy nhóm chat", [], 500);
            }

            DB::commit();
            $message = "Thao tác thành công";

            return $this->respondSuccess([], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Chat $chat
     * @return \Illuminate\Http\Response
     */
    public function detail($id)
    {
        DB::beginTransaction();
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            $chat = Chat::where("id", $id)->select(
                '*',
                DB::raw("'#669900' as 'color'")
            )->first();

            if (!$chat) {
                return $this->respondWithError("Không tìm thấy tin nhắn", [], 500);
            }

            return $this->respondSuccess($chat);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            $input = $request->only(['message']);

            $chat = Chat::where("id", $id)->first();
            if (!$chat) {
                return $this->respondWithError("Không tìm thấy tin nhắn", [], 500);
            }

            $input["status"] = "edit";

            Chat::where("id", $id)->update($input);

            DB::commit();
            $message = "Cập nhật tin nhắn thành công";

            return $this->respondSuccess($chat, $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        DB::beginTransaction();
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            $chat = Chat::where("id", $id)->first();
            if (!$chat) {
                return $this->respondWithError("Không tìm thấy tin nhắn", [], 500);
            }

            $chat->delete();

            DB::commit();
            $message = "Xóa tin nhắn thành công";

            return $this->respondSuccess($chat, $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }
}
