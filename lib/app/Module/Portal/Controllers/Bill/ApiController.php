<?php

namespace App\Module\Portal\Controllers\Bill;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\BillEntity;
use Illuminate\Http\Request;

class ApiController extends Controller
{
  /**
   * All Utils instance.
   *
   */
  protected $entity;
  /**
   * Constructor
   *
   * @param ProductUtils $product
   * @return void
   */
  public function __construct(
    BillEntity $entity
  ) {
    $this->entity = $entity;
  }

  public function list(Request $request)
  {
    $data = $this->entity->all($request);
    return $this->respondSuccess($data);
  }

  public function detail($id)
  {
    $data = $this->entity->find($id);
    return $this->respondSuccess($data);
  }

  public function create(Request $request)
  {
    $data = $this->entity->create($request->all());
    return $this->respondSuccess($data);
  }

  public function update(Request $request, $id)
  {
    $request->merge(['id' => $id]);
    $data = $this->entity->update($request->all());
    return $this->respondSuccess($data);
  }

  public function delete($id)
  {
    $data = $this->entity->delete($id);
    return $this->respondSuccess($data);
  }

  public function pay(Request $request)
  {
    $data = $this->entity->pay($request);
    return $this->respondSuccess($data);
  }

  public function getPaymentCart(Request $request)
  {
    $data = $this->entity->getPaymentCart($request->all());
    return $this->respondSuccess($data);
  }

  public function transfer(Request $request)
  {
    $data = $this->entity->transfer($request);
    return $this->respondSuccess($data);
  }
}
