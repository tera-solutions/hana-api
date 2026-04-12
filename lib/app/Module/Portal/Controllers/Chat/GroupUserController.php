<?php

namespace App\Module\Portal\Controllers\Chat;

use App\Module\Portal\Model\Chat\GroupUser;
use App\Http\Controllers\Controller;
use App\Module\Portal\Model\Chat\Chat;
use App\Module\Portal\Model\Chat\GroupChat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Excel;

class GroupUserController extends Controller
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
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }
            $user_id = Auth::guard('api')->user()->id;

            $groupUser = GroupUser::with(["group", "user"])
                ->where("user_id", $user_id);

            if (isset($request->keyword) && $request->keyword) {
                $query = "(name LIKE '%" . $request->keyword . "%' OR ";
                $query .= "code LIKE '%" . $request->keyword . "%' OR ";
                $query .= "description LIKE '%" . $request->keyword . "%')";

                $groupUser->whereRaw($query);
            }

            if (isset($request->type) && $request->type) {
                $groupUser->where("type", $request->type);
            }

            $sort_field = "created_at";
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

    public function listFriend(Request $request)
    {
        try {
            $user = User::select([
                "id",
                "username",
                "full_name",
                "avatar"
            ]);

            if (isset($request->keyword) && $request->keyword) {
                $query = "(LOWER(users.username) LIKE '%" . strtolower($request->keyword) . "%' OR ";
                $query .= "LOWER(users.phone) LIKE '%" . strtolower($request->keyword) . "%' OR ";
                $query .= "LOWER(users.full_name) LIKE '%" . strtolower($request->keyword) . "%')";

                $user->whereRaw($query);
            }

            if (!empty($request->api_type) && $request->api_type === "add_member") {
                $groupUser = GroupUser::where("group_id", $request->group_id)->get();
                $ids = [];
                foreach ($groupUser as $value) {
                    array_push($ids, $value->user_id);
                }

                $user->whereNotIn("id", $ids);
            }

            if (isset($request->full_name) && $request->full_name) {
                $user->where("users.full_name", "LIKE", "%$request->full_name%");
            }
            if (isset($request->phone) && $request->phone) {
                $user->where("users.phone", "LIKE", "%$request->phone%");
            }

            if (isset($request->username) && $request->username) {
                $user->where("users.username", "LIKE", "%$request->username%");
            }

            if (isset($request->position_key)) {
                $user->where("users.position_key", $request->position_key);
            }

            if (isset($request->status) && $request->status) {
                $user->where("users.status", $request->status);
            }

            $sort_field = "users.created_at";
            $sort_des = "desc";

            if (isset($request->order_field) && $request->order_field) {
                $sort_field = $request->order_field;
            }

            if (isset($request->order_by) && $request->order_by) {
                $sort_des = $request->order_by;
            }

            $user->orderBy($sort_field, $sort_des);

            $data = $user->paginate($request->limit);

            return $this->respondSuccess($data);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    public function getDetailFriend(Request $request)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondSuccess([]);
            }

            $user_id = Auth::guard('api')->user()->id;

            if (empty($request->username)) {
                return $this->respondSuccess([]);
            }

            $user = User::select([
                "id",
                "username",
                "full_name",
                "avatar"
            ])
                ->where("username", $request->username)
                ->first();

            if (!$user) {
                $group = GroupChat::where("code", $request->username)
                    ->withCount([
                        'users as user_total'
                    ])
                    ->addSelect([
                        "code as group_code",
                    ])
                    ->first();

                return $this->respondSuccess($group);
            }

            if ($user) {
                $groupUser = GroupUser::where("user_id", $user->id)
                    ->first();

                $user->user_id = $user->id;
                if ($groupUser) {
                    $group = GroupChat::where("id", $groupUser->group_id)
                        ->first();
                    if ($group) {
                        $user->type = $group->type;
                        $user->group_code = $group->code;
                        $user->group_id = $group->id;
                        if ($groupUser) {
                            $user->is_block = $groupUser->is_block;
                        }
                    }
                }
            }


            return $this->respondSuccess($user);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\GroupUser $groupUser
     * @return \Illuminate\Http\Response
     */
    public function detail(Request $request)
    {
        DB::beginTransaction();
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("Không có quyền truy cập", [], 401);
            }

            if (empty($request->group_id)) {
                return $this->respondWithError("Không tìm thấy nhóm chat", [], 500);
            }


            if (empty($request->user_id)) {
                return $this->respondWithError("Không tìm thấy người dùng", [], 500);
            }

            $groupUser = GroupUser::with(["group", "user"])
                ->where("user_id", $request->user_id)
                ->where("group_id", $request->group_id)
                ->select(
                    '*'
                )->first();

            if (!$groupUser) {
                return $this->respondWithError("Không tìm thấy người dùng", [], 500);
            }

            return $this->respondSuccess($groupUser);
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

            $groupUser = GroupUser::where("id", $id)->first();
            if (!$groupUser) {
                return $this->respondWithError("Không tìm thấy Nhóm chat", [], 500);
            }

            $groupUser->delete();
            DB::commit();
            $message = "Xóa Nhóm chat thành công";

            return $this->respondSuccess($groupUser, $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }
}
