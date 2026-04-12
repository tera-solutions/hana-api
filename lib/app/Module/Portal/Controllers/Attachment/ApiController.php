<?php

namespace App\Module\Portal\Controllers\Attachment;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\AttachmentEntity;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    protected $attachment;

    public function __construct(AttachmentEntity $attachment)
    {
        $this->attachment = $attachment;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $data = $this->attachment->all($request);
        return $this->respondSuccess($data);
    }

    public function detail($id)
    {
        $result = $this->attachment->find($id);
        return $this->respondSuccess($result);
    }

    public function download(Request $request, $id)
    {
        $result = $this->attachment->download($request, $id);
        return $this->respondSuccess($result);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $input['id'] = $id;
        $result = $this->attachment->update($input);
        $message = "Cập nhật tập đính kèm thành công";
        return $this->respondSuccess($result, $message);
    }

    public function delete(Request $request, $id)
    {
        $result = $this->attachment->deleteAttachment($request, $id);
        $message = "Xóa tập đính kèm thành công";
        return $this->respondSuccess($result, $message);
    }
}
