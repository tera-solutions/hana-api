<?php

namespace App\Module\Portal\Controllers\Chat;

use App\Helpers\Task;
use App\Module\Portal\Model\Chat\GroupChat;
use App\Http\Controllers\Controller;
use App\Module\Portal\Model\Chat\Chat;
use App\Module\Portal\Model\Chat\GroupUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Excel;

class GroupController extends Controller
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
            $position_key = Auth::guard('api')->user()->position_key;

            $group = GroupChat::select([
                "group_chats.*"
            ]);

            if (isset($request->keyword) && $request->keyword) {
                $query = "(group_chats.name LIKE '%" . $request->keyword . "%' OR ";
                $query .= "group_chats.code LIKE '%" . $request->keyword . "%')";

                $group->whereRaw($query);
            }

            if (isset($request->type) && $request->type) {
                $group->where("group_chats.type", $request->type);
            }

            $sort_field = "group_chats.created_at";
            $sort_des = "desc";

            if (isset($request->order_field) && $request->order_field) {
                $sort_field = $request->order_field;
            }

            if (isset($request->order_by) && $request->order_by) {
                $sort_des = $request->order_by;
            }

            $group->orderBy($sort_field, $sort_des);

            $data = $group->paginate($request->limit);

            return $this->respondSuccess($data);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    public function listMember(Request $request)
    {
        try {
            $group = GroupChat::where("id", $request->id)->first();

            if (!$group) {
                return $this->respondSuccess([]);
            }

            $groupUser = GroupUser::leftJoin("db_tera_auth.users as user", "user.id", "group_users.user_id")
                ->where("group_users.group_id", $group->id)
                ->select(
                    'group_users.*',
                    'user.full_name',
                    'user.avatar as avatar_url'
                );

            if (isset($request->keyword) && $request->keyword) {
                $query = "(LOWER(user.username) LIKE '%" . strtolower($request->keyword) . "%' OR ";
                $query .= "LOWER(user.phone) LIKE '%" . strtolower($request->keyword) . "%' OR ";
                $query .= "LOWER(user.full_name) LIKE '%" . strtolower($request->keyword) . "%')";

                $groupUser->whereRaw($query);
            }

            if (isset($request->full_name) && $request->full_name) {
                $groupUser->where("user.full_name", "LIKE", "%$request->full_name%");
            }
            if (isset($request->phone) && $request->phone) {
                $groupUser->where("user.phone", "LIKE", "%$request->phone%");
            }

            if (isset($request->username) && $request->username) {
                $groupUser->where("user.username", "LIKE", "%$request->username%");
            }


            $sort_field = "group_users.created_at";
            $sort_des = "desc";

            if (isset($request->order_field) && $request->order_field) {
                $sort_field = $request->order_field;
            }

            if (isset($request->order_by) && $request->order_by) {
                $sort_des = $request->order_by;
            }

            $groupUser->orderBy($sort_field, $sort_des);

            $data = $groupUser->paginate($request->limit);

            return $this->respondSuccess($data);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    public function deleteMember(Request $request)
    {
        DB::beginTransaction();
        try {
            if (empty($request->id)) {
                return $this->respondWithError("Không tìm thấy nhóm", [], 500);
            }

            if (empty($request->user_id)) {
                return $this->respondWithError("Không tìm thấy thành viên trong nhóm", [], 500);
            }


            $group = GroupChat::where("id", $request->id)->first();

            if (!$group) {
                return $this->respondWithError("Không tìm thấy nhóm", [], 500);
            }

            $groupUser = GroupUser::where("user_id", $request->user_id)
                ->where("group_id", $group->id)
                ->first();

            if (!$groupUser) {
                return $this->respondWithError("Không tìm thấy thành viên trong nhóm", [], 500);
            }
            $groupUser->delete();

            Chat::where("created_by", $request->user_id)->where("group_code", $group->code)->delete();

            DB::commit();

            return $this->respondSuccess([], "Thao tác thành công");
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    public function blockMember(Request $request)
    {
        DB::beginTransaction();
        try {
            if (empty($request->id)) {
                return $this->respondWithError("Không tìm thấy nhóm", [], 500);
            }

            if (empty($request->user_id)) {
                return $this->respondWithError("Không tìm thấy thành viên trong nhóm", [], 500);
            }

            if (!isset($request->is_block)) {
                return $this->respondWithError("Vui lòng chọn thao tác", [], 500);
            }

            $group = GroupChat::where("id", $request->id)->first();

            if (!$group) {
                return $this->respondWithError("Không tìm thấy nhóm", [], 500);
            }

            $groupUser = GroupUser::where("user_id", $request->user_id)
                ->where("group_id", $group->id)
                ->first();

            if (!$groupUser) {
                return $this->respondWithError("Không tìm thấy thành viên trong nhóm", [], 500);
            }
            $groupUser->is_block = $request->is_block;
            Chat::where("created_by", $request->user_id)->where("group_code", $group->code)->delete();

            $groupUser->save();

            DB::commit();

            return $this->respondSuccess([], "Thao tác thành công");
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }


    public function addMember(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            if (empty($request->ids) || count($request->ids) < 1) {
                return $this->respondWithError("Vui lòng chọn ít nhất 1 thành viên", [], 500);
            }

            if (empty($request->group_id)) {
                return $this->respondWithError("Vui lòng chọn 1 nhóm", [], 500);
            }

            $group = GroupChat::where("id", $request->group_id)->first();

            if (empty($group)) {
                DB::rollBack();
                return $this->respondWithError("Lỗi trong quá trình gửi tin nhắn", [], 500);
            }

            $dataGroupUser = [];

            foreach ($request->ids as $value) {
                $checkExit = GroupUser::where("group_id", $group->id)->where("user_id", $value)->first();
                if (!$checkExit) {
                    $item =   [
                        'group_id' => $group->id,
                        'user_id' => $value,
                        'type' => "user",
                        'created_at' => now()
                    ];

                    array_push($dataGroupUser, $item);
                }
            }

            if (count($dataGroupUser) === 0) {
                return $this->respondWithError("Vui lòng chọn ít nhất 1 thành viên", $dataGroupUser, 500);
            }

            $groupUser = GroupUser::insert($dataGroupUser);

            if (empty($groupUser)) {
                DB::rollBack();
                return $this->respondWithError("Lỗi trong quá trình gửi tin nhắn", [], 500);
            }

            DB::commit();
            return $this->respondSuccess($group, "Thêm nhóm chat thành công");
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            if (empty($request->ids) || count($request->ids) < 2) {
                return $this->respondWithError("Vui lòng chọn ít nhất 2 thành viên", [], 500);
            }

            if (empty($request->name)) {
                return $this->respondWithError("Vui lòng nhập tên nhóm", [], 500);
            }

            $user_id = Auth::guard('api')->user()->id;
            $ref_count = Task::setAndGetReferenceCount("group_chat");
            $ref_no = Task::generateReferenceNumber("group_chat", $ref_count, "GROUP");

            $dataGroup = [
                'created_by' => $user_id,
                'code' => $ref_no,
                'name' => $request->name,
                'type' => "group",
                'created_at' => now(),
                'updated_at' => now()
            ];

            $group = GroupChat::create($dataGroup);

            if (empty($group)) {
                DB::rollBack();
                return $this->respondWithError("Lỗi trong quá trình gửi tin nhắn", [], 500);
            }

            $dataGroupUser = [
                [
                    'group_id' => $group->id,
                    'user_id' => $user_id,
                    'type' => "user",
                    'created_at' => now()
                ]
            ];

            foreach ($request->ids as $value) {
                $item =   [
                    'group_id' => $group->id,
                    'user_id' => $value,
                    'type' => "user",
                    'created_at' => now()
                ];

                array_push($dataGroupUser, $item);
            }

            $groupUser = GroupUser::insert($dataGroupUser);

            if (empty($groupUser)) {
                DB::rollBack();
                return $this->respondWithError("Lỗi trong quá trình gửi tin nhắn", [], 500);
            }

            DB::commit();
            return $this->respondSuccess($group, "Thêm nhóm chat thành công");
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Group $group
     * @return \Illuminate\Http\Response
     */
    public function detail($id)
    {
        DB::beginTransaction();
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            $group = GroupChat::where("id", $id)->select(
                '*',
                DB::raw("'#669900' as 'color'")
            )->first();

            if (!$group) {
                return $this->respondWithError("Không tìm thấy nhóm", [], 500);
            }

            return $this->respondSuccess($group);
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

            $input = $request->only(['name']);

            $group = GroupChat::where("id", $id)->first();
            if (!$group) {
                return $this->respondWithError("Không tìm thấy nhóm", [], 500);
            }

            if (isset($input['avatar'])) {
                $urlImage = !empty($input["avatar"]) ? str_replace(url("/"), "", $input["avatar"]) : "";
                $input["avatar"] = $urlImage;
            }

            GroupChat::where("id", $id)->update($input);

            DB::commit();
            $message = "Cập nhật nhóm thành công";

            return $this->respondSuccess($group, $message);
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

            $group = GroupChat::where("id", $id)->first();
            if (!$group) {
                return $this->respondWithError("Không tìm thấy nhóm", [], 500);
            }

            $group->delete();
            DB::commit();
            $message = "Xóa nhóm thành công";

            return $this->respondSuccess($group, $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }
}
