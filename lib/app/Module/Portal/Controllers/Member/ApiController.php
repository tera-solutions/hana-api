<?php

namespace App\Module\Portal\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\MemberEntity;
use Illuminate\Http\Request;

class ApiController extends Controller
{
  protected $member;

  public function __construct(MemberEntity $member)
  {
    $this->member = $member;
  }
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function list(Request $request)
  {
    $data = $this->member->all($request);
    return $this->respondSuccess($data);
  }

  public function detail($id)
  {
    $result = $this->member->find($id);
    return $this->respondSuccess($result);
  }

  public function create(Request $request)
  {
      $input = $request->all();
      $data = $this->member->create($input);
      return $data->json();
  }

  public function update(Request $request, $id)
  {
    $input = $request->all();
    $input['id'] = $id;
    $result = $this->member->update($input);
    $message = "Cập nhật thành viên thành công";
    return $this->respondSuccess($result, $message);
  }

  public function delete($id)
  {
    $result = $this->member->delete($id);
    $message = "Xóa thành viên thành công";
    return $this->respondSuccess($result, $message);
  }

  public function changePassword(Request $request)
  {
    $result = $this->member->changePassword($request);
    return $this->respondSuccess($result);
  }

  public function permission(Request $request)
  {
    $result = $this->member->permission($request);
    return $this->respondSuccess($result);
  }

  public function addToModule(Request $request)
  {
    $result = $this->member->addToModule($request);
    return $this->respondSuccess($result);
  }

  public function removeMemberModule(Request $request)
  {
    $result = $this->member->removeMemberModule($request);
    return $this->respondSuccess($result);
  }

  public function updateStatus(Request $request)
  {
    $result = $this->member->updateStatus($request);
    return $this->respondSuccess($result);
  }

  public function configRole(Request $request)
  {
    $result = $this->member->configRole($request);
    return $this->respondSuccess($result);
  }
}
