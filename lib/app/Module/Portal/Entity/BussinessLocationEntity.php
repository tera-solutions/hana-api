<?php

namespace App\Module\Portal\Entity;

use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use App\Module\Portal\Repository\BussinessLocationRepository;
use App\Module\Portal\Validation\BussinessLocation\BussinessLocationCreateValidator;
use App\Module\Portal\Validation\BussinessLocation\BussinessLocationUpdateValidator;
use App\Module\Portal\Permission\BussinessLocationPermission;
use Package\Exception\AuthorizationException;
use Package\Exception\ValidationException;

class BussinessLocationEntity extends AbstractEntity implements EntityInterface
{

  /**
   * @var BussinessLocationRepository
   */
  protected $repository;

  /**
   * @var BussinessLocationCreateValidator
   */
  protected $createValidator;

  /**
   * @var BussinessLocationUpdateValidator
   */
  protected $updateValidator;

  /**
   * @var
   */
  protected $errors;

  protected $permission;
  /**
   * RegisterEntity constructor.
   * @param BussinessLocationRepository $repository
   * @param BussinessLocationCreateValidator $createValidator
   * @param BussinessLocationUpdateValidator $updateValidator
   * @param BussinessLocationPermission $permission
   */
  public function __construct(
    BussinessLocationRepository $repository,
    BussinessLocationCreateValidator $createValidator,
    BussinessLocationUpdateValidator $updateValidator,
    BussinessLocationPermission $permission
  ) {
    $this->repository = $repository;
    $this->createValidator = $createValidator;
    $this->updateValidator = $updateValidator;
    $this->permission = $permission;
  }

    public function syncDataLocal($request)
  {
    if (!$this->permission->checkKey('create')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }

    return $this->repository->syncDataLocal($request);
  }

  public function createData($request)
  {
    if (!$this->permission->checkKey('create')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }

    $data = $request->all();

    if (!$this->createValidator->with($data)->passes()) {
      $this->errors = $this->createValidator->errors();
      throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
    }
    return $this->repository->createData($request);
  }

  public function updateData($request, $id)
  {
    if (!$this->permission->checkKey('update')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }

    $data = $request->all();
    if (!$this->updateValidator->with($data)->passes()) {
      $this->errors = $this->updateValidator->errors();
      throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
    }
    return $this->repository->updateData($request, $id);
  }

  public function importData($request)
  {
    return $this->repository->importData($request);
  }
}
