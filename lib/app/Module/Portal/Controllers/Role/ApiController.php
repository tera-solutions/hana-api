<?php

namespace App\Module\Portal\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\RoleEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Package\Exception\HttpException;

class ApiController extends Controller
{
  protected $entity;

  public function __construct(RoleEntity $entity)
  {
    // $typeUser = Auth::guard('api')->user()->type;
    // if ($typeUser != 'owner') {
    //   throw new HttpException("Tài khoản của bạn không phải là chủ sở hữu nên không thể dùng chức năng này !");
    // }
    $this->entity = $entity;
  }
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function list(Request $request)
  {
    $data = $this->entity->all($request);
    return $this->respondSuccess($data);
  }


  public function create(Request $request)
  {
    $input = $request->all();
    $input['created_by'] = auth()->guard('api')->user()->id;
    $data = $this->entity->create($input);
    return $this->respondSuccess($data);
  }

  public function detail($id)
  {
    $data = $this->entity->find($id);
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
    $result = $this->entity->find($id);
    if (!$result) {
      return $this->respondWithError("Không tìm thấy quyền", [], 500);
    }
    $input = $request->all();
    $input['id'] = $id;
    $input['updated_by'] = auth()->guard('api')->user()->id;
    $result = $this->entity->update($input);
    $message = "Cập nhật quyền thành công";
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
    $entity = $this->entity->find($id);
    if (!$entity) {
      return $this->respondWithError("Không tìm thấy dữ liệu", [], 500);
    }
    $result = $this->entity->delete($id);
    $message = "Xóa quyền thành công";
    return $this->respondSuccess($result, $message);
  }

  public function listModule()
  {
    $result = $this->entity->listModule();
    return $this->respondSuccess($result);
  }

  public function roleHasPermission(Request $request)
  {
    $result = $this->entity->roleHasPermission($request);
    return $this->respondSuccess($result);
  }

  public function configPermission(Request $request)
  {
    $result = $this->entity->configPermission($request);
    return $this->respondSuccess($result);
  }

  public function roleHasPermissionDetail(Request $request)
  {
    $result = $this->entity->roleHasPermissionDetail($request);

    return $this->respondSuccess($result);
  }

  public function listConfigControl(Request $request)
  {
    $result = $this->entity->listConfigControl($request->all());
    return $this->respondSuccess($result);
  }

  public function getPermissionDefault()
  {
    $result = $this->entity->getPermissionDefault();
    return $this->respondSuccess($result);
  }
}
