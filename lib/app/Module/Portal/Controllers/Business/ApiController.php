<?php

namespace App\Module\Portal\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\BusinessEntity;
use App\Module\Portal\Entity\entityEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
  protected $entity;

  public function __construct(BusinessEntity $entity)
  {
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

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   */
  public function create(Request $request)
  {
    $input = $request->all();
    if (Auth::guard('api')->check()) {
      $user_id = Auth::guard('api')->user()->id;
      $input['created_by'] = $user_id;
    }
    $result = $this->entity->create($input);
    $message = "Thêm doanh nghiệp thành công";
    return $this->respondSuccess($result, $message);
  }

  /**
   * Display the specified resource.
   *
   * @param  \App\contract $entity
   * @return \Illuminate\Http\Response
   */
  public function detail($id)
  {
    $result = $this->entity->find($id);
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
    $result = $this->entity->find($id);
    if (!$result) {
      return $this->respondWithError("Không tìm thấy doanh nghiệp", 404);
    }
    $input = $request->all();
    $input['id'] = $id;
    $result = $this->entity->update($input);
    $message = "Cập nhật doanh nghiệp thành công";
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
    $notification = $this->entity->find($id);
    if (!$notification) {
      return $this->respondWithError("Không tìm thấy dữ liệu", [], 500);
    }
    $result = $this->entity->delete($id);
    $message = "Xóa doanh nghiệp thành công";
    return $this->respondSuccess($result, $message);
  }

  public function register(Request $request)
  {
    $result = $this->entity->register($request);
    return $this->respondSuccess($result);
  }

  public function save(Request $request)
  {
    $result = $this->entity->save($request);
    return $this->respondSuccess($result);
  }

  public function getInfo()
  {
    $info = $this->entity->getInfo();
    return $this->respondSuccess($info);
  }
}
