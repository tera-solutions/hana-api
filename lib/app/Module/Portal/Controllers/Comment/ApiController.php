<?php

namespace App\Module\Portal\Controllers\Comment;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\CommentEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    protected $comment;

    public function __construct(CommentEntity $comment)
    {
        $this->comment = $comment;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $data = $this->comment->all($request);
        return $this->respondSuccess($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $input = $request->all();
        if (empty($input['type'])) {
            $input['type'] = "bình luận";
        }
        $user_id = Auth::guard('api')->user()->id;
        $input['created_by'] = $user_id;
        $result = $this->comment->create($input);
        $message = "Thêm bình luận thành công";
        return $this->respondSuccess($result, $message);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\contract $comment
     * @return \Illuminate\Http\Response
     */
    public function detail($id)
    {
        $result = $this->comment->find($id);
        return $this->respondSuccess($result);
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
        $result = $this->comment->find($id);
        if (!$result) {
            return $this->respondWithError("Không tìm thấy bình luận", 404);
        }
        $input = $request->all();
        $input['id'] = $id;
        $result = $this->comment->update($input);
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
        $notification = $this->comment->find($id);
        if (!$notification) {
            return $this->respondWithError("Không tìm thấy dữ liệu", [], 500);
        }
        $result = $this->comment->delete($id);
        $message = "Xóa bình luận thành công";
        return $this->respondSuccess($result, $message);
    }
}
