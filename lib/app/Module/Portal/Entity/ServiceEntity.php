<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Model\BusinessService;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use App\Module\Portal\Repository\ServiceRepository;
use App\Module\Portal\Permission\ServicePermission;
use Package\Exception\HttpException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class ServiceEntity extends AbstractEntity implements EntityInterface
{

  /**
   * @var
   */
  protected $repository;

  protected $createValidator;

  protected $updateValidator;

  /**
   * @var
   */
  protected $errors;


  protected $permission;
  /**
   * RegisterEntity constructor.
   * @param ServiceRepository $repository
   * @param ServicePermission $updateValidator
   */
  public function __construct(
    ServiceRepository $repository,
    ServicePermission $permission
  ) {
    $this->repository = $repository;
    $this->permission = $permission;
  }

  public function listAvailability($request)
  {
    return $this->repository->listAvailability($request);
  }

  public function listPackage($request)
  {
    return $this->repository->listPackage($request);
  }

  public function calculateOldPackage($request)
  {
    return $this->repository->calculateOldPackage($request);
  }
}
