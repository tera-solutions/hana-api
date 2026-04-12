<?php

namespace App\Module\Portal\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\NotificationEntity;
use Illuminate\Http\Request;


class ApiController extends Controller
{
    protected $notification;

    public function __construct(NotificationEntity $notification)
    {
        $this->notification = $notification;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $data = $this->notification->all($request);
        return $this->respondSuccess($data);
    }

    public function readNotification($id)
    {
        $data = $this->notification->read($id);
        return $this->respondSuccess($data);
    }

    public function create(Request $request)
    {

        $data = $this->notification->create($request);
        return $this->respondSuccess($data);
    }

    public function detail($id)
    {
        $data = $this->notification->find($id);
        return $this->respondSuccess($data);
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
        $result = $this->notification->find($id);
        if (!$result) {
            return $this->respondWithError("Không tìm thấy phòng ban", [], 500);
        }
        $input = $request->all();
        $input['id'] = $id;
        $result = $this->notification->update($input);
        $message = "Cập nhật Bình luận thành công";
        return $this->respondSuccess($result, $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $notification = $this->notification->find($id);
        if (!$notification) {
            return $this->respondWithError("Không tìm thấy dữ liệu", [], 500);
        }
        $result = $this->notification->delete($id);
        $message = "Xóa Thông báo thành công";
        return $this->respondSuccess($result, $message);
    }
}
