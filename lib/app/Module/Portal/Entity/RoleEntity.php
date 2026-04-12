<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Model\BusinessService;
use App\Module\Portal\Model\Epic;
use App\Module\Portal\Model\GroupPageControl;
use App\Module\Portal\Model\GroupRolePermission;
use App\Module\Portal\Model\Module;
use App\Module\Portal\Model\Role;
use App\Module\Portal\Model\RolePermission as ModelRolePermission;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use App\Module\Portal\Repository\RoleRepository;
use App\Module\Portal\Validation\Role\RoleCreateValidator;
use App\Module\Portal\Validation\Role\RoleUpdateValidator;
use App\Module\Portal\Permission\RolePermission;
use Illuminate\Support\Facades\Auth;
use Package\Exception\AuthorizationException;
use Package\Exception\DatabaseException;
use Package\Exception\ValidationException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class RoleEntity extends AbstractEntity implements EntityInterface
{

  /**
   * @var RoleRepository
   */
  protected $repository;

  /**
   * @var RoleCreateValidator
   */
  protected $createValidator;

  /**
   * @var RoleUpdateValidator
   *
   */
  protected $updateValidator;

  /**
   * @var
   */
  protected $errors;

  /**
   * @var RolePermission
   */
  protected $permission;
  /**
   * RegisterEntity constructor.
   * @param RoleRepository $repository
   * @param RoleCreateValidator $createValidator
   * @param RoleUpdateValidator $updateValidator
   * @param RolePermission $permission
   */
  public function __construct(
    RoleRepository $repository,
    RoleCreateValidator $createValidator,
    RoleUpdateValidator $updateValidator,
    RolePermission $permission
  ) {
    $this->repository = $repository;
    $this->createValidator = $createValidator;
    $this->updateValidator = $updateValidator;
    $this->permission = $permission;
  }

  public function create($request)
  {
    if (!$this->permission->checkKey('create')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }

    if (!$this->createValidator->with($request)->passes()) {
      $this->errors = $this->createValidator->errors();
      throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
    }
    return $this->repository->create($request);
  }

  public function update($request)
  {
    if (!$this->permission->checkKey('update')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }

    if (!$this->updateValidator->with($request)->passes()) {
      $this->errors = $this->updateValidator->errors();
      throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
    }
    return $this->repository->update($request);
  }

  public function listModule()
  {
    if (!$this->permission->checkKey('a')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->listModule();
  }

  public function roleHasPermission($request)
  {
    if (!$this->permission->checkKey('a')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->roleHasPermission($request);
  }

  public function roleHasPermissionDetail($request)
  {
    if (!$this->permission->checkKey('a')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->roleHasPermissionDetail($request);
  }

  public function listConfigControl($input)
  {
    if (!$this->permission->checkKey('a')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->listConfigControl($input);
  }

  public function getPermissionDefault()
  {
    if (!$this->permission->checkKey('a')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->getPermissionDefault();
  }

  public function configPermission($request)
  {
    if (!$this->permission->checkKey('a')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->configPermission($request);
  }
}
