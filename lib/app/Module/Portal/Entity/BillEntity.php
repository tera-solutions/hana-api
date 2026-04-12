<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Validation\Bill\BillCreateValidator;
use App\Module\Portal\Permission\BillPermission;
use App\Module\Portal\Repository\BillRepository;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use Package\Exception\AuthorizationException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class BillEntity extends AbstractEntity implements EntityInterface
{

  protected $repository;

  protected $createValidator;

  protected $errors;

  protected $permission;

  public function __construct(
    BillRepository $repository,
    BillCreateValidator $createValidator,
    BillPermission $permission
  ) {
    $this->repository = $repository;
    $this->createValidator = $createValidator;
    $this->permission = $permission;
  }

  public function create($request)
  {
    if (!$this->permission) {
      throw new AuthorizationException();
    }

    if (!$this->permission->checkKey('create')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }

    if (!empty($request['selected_items'])) {
      $itemsCart = $this->repository->getItemsInCart($request['selected_items']);
      $request['items'] = collect($itemsCart)->toArray();
      unset($request['selected_items']);
    }

    return $this->repository->create($request);
  }

  public function pay($request)
  {
    if (!$this->permission) {
      throw new AuthorizationException();
    }

    if (!$this->permission->checkKey('pay')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->pay($request);
  }

  public function transfer($request)
  {
    if (!$this->permission) {
      throw new AuthorizationException();
    }

    if (!$this->permission->checkKey('transfer')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->transfer($request);
  }
}
