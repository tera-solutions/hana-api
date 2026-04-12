<?php

namespace App\Module\Portal\Repository;

use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Exception;
use Package\Exception\DatabaseException;
use App\Models\Media;
use App\Module\Portal\Model\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Package\Exception\HttpException;

class BusinessRepository extends BasicEntity implements RepositoryInterface
{

  public $table;

  public $primaryKey;

  public $fillable;

  public $hidden;

  public function __construct()
  {
    $business = new Business();
    $this->table =     $business->getTable();
    $this->fillable =      $business->getFillable();
    $this->hidden =      $business->getHidden();
    $this->primaryKey =      $business->getKeyName();
    array_push($this->fillable, $this->primaryKey);
  }

  public function all($request)
  {
    $business = Business::select([
      "*",
    ]);

    $sort_field = "created_at";
    $sort_des = "desc";

    if (isset($request->order_field) && $request->order_field) {
      $sort_field = $request->order_field;
    }

    if (isset($request->order_by) && $request->order_by) {
      $sort_des = $request->order_by;
    }

    $business->orderBy($sort_field, $sort_des);

    $data = $business->paginate($request->limit);
    return $data;
  }

  public function find($id)
  {
    $data = Business::select([
      "*",
    ])->find($id);

    if (!$data) {
      throw new HttpException("Không tìm thấy data");
    }
    return $data;
  }

  public function create($data)
  {
    try {
      $result = Business::create($data);

      return $result;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }
  public function createManyOfRow($data)
  {
    try {
      $model = $this->CreateManyRow($data);

      return $model;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function update($data)
  {
    try {
      $business = Business::where('id', $data['id'])->first();
      if (!$business) {
        throw new HttpException("Không tìm thấy data");
      }
      $business->update($data);

      return $business;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function allTrash($pagin = null)
  {
    return  $this->findAllTrash($pagin);
  }

  public function trash($id, $trash = true)
  {
    try {
      $this->id = $id;
      $model = $this->TrashOrRecover($id, $trash);

      return $model;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function delete($id)
  {
    try {
      $business = Business::where('id', $id)->first();
      if (!$business) {
        throw new HttpException("Không tìm thấy data");
      }
      $business->delete();
      return $business;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }
}
