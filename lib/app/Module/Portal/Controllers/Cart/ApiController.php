<?php

namespace App\Module\Portal\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\BussinessLocationEntity;
use App\Module\Portal\Entity\CartEntity;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

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
    CartEntity $entity
  ) {
    $this->entity = $entity;
  }

  public function list(Request $request)
  {
    $data = $this->entity->all($request);
    return $this->respondSuccess($data);
  }


  public function create(Request $request)
  {
    $data = $this->entity->create($request->all());
    return $this->respondSuccess($data, "Thêm vào giỏ hàng thành công !");
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

  public function replace(Request $request, $id)
  {
    $request->merge(['id' => $id]);
    $data = $this->entity->replace($request->all());
    return $this->respondSuccess($data);
  }

  public function getPaymentCart(Request $request)
  {
    $data = $this->entity->getPaymentCart($request->all());
    return $this->respondSuccess($data);
  }

  public function getCountCart()
  {
    $data = $this->entity->getCountCart();
    return $this->respondSuccess($data);
  }
}
