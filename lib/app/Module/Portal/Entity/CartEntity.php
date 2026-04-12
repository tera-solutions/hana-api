<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Permission\CartPermission;
use App\Module\Portal\Repository\CartRepository;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use Package\Exception\AuthorizationException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class CartEntity extends AbstractEntity implements EntityInterface
{


  protected $repository;


  protected $errors;

  protected $permission;

  public function __construct(
    CartRepository $repository,
    CartPermission $permission
  ) {
    $this->repository = $repository;
    $this->permission = $permission;
  }

  public function create($request)
  {
    if (!$this->permission) {
      throw new AuthorizationException();
    }

    if (!$this->permission->checkKey('created')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->create($request);
  }

  public function update($request)
  {
    if (!$this->permission) {
      throw new AuthorizationException();
    }

    if (!$this->permission->checkKey('update')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->update($request);
  }

  public function replace($request)
  {
    if (!$this->permission) {
      throw new AuthorizationException();
    }

    if (!$this->permission->checkKey('replace')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->replace($request);
  }

  public function getPaymentCart($request)
  {
    return $this->repository->getPaymentCart($request);
  }

  public function getCountCart()
  {
    return $this->repository->getCountCart();
  }
}
