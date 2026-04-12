<?php

namespace App\Module\Portal\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\ServiceEntity;
use Illuminate\Http\Request;


class ApiController extends Controller
{
  protected $entity;

  public function __construct(ServiceEntity $entity)
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

  public function listAvailability(Request $request)
  {
    $data = $this->entity->listAvailability($request);
    return $this->respondSuccess($data);
  }

  public function detail($id)
  {
    $data = $this->entity->find($id);
    return $this->respondSuccess($data);
  }

  public function listPackage(Request $request)
  {
    $data = $this->entity->listPackage($request);
    return $this->respondSuccess($data);
  }

  public function calculateOldPackage(Request $request)
  {
    $data = $this->entity->calculateOldPackage($request);
    return $this->respondSuccess($data);
  }
}
