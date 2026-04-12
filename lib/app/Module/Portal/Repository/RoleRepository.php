<?php

namespace App\Module\Portal\Repository;

use App\Models\Role;
use App\Module\Portal\Helpers\Helper;
use App\Module\Portal\Helpers\SyncPermissionRole;
use App\Module\Portal\Model\Business;
use App\Module\Portal\Model\BusinessService;
use App\Module\Portal\Model\Epic;
use App\Module\Portal\Model\GroupPageControl;
use Illuminate\Support\Facades\DB;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Exception;
use App\Module\Portal\Model\Module;
use App\Module\Portal\Model\ModulePermission;
use App\Module\Portal\Model\GroupRole;
use App\Module\Portal\Model\GroupRolePermission;
use App\Module\Portal\Model\RolePermission;
use App\Module\Portal\Model\Service;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Package\Exception\DatabaseException;
use Package\Exception\HttpException;

class RoleRepository extends BasicEntity implements RepositoryInterface
{

  public $table;

  public $primaryKey;

  public $fillable;

  public $hidden;

  public function __construct()
  {
    $module = new GroupRole();
    $this->table = $module->getTable();
    $this->fillable = $module->getFillable();
    $this->hidden = $module->getHidden();
    $this->primaryKey = $module->getKeyName();
    array_push($this->fillable, $this->primaryKey);
  }

  public function all($request)
  {
    $limit = 10;
    $business = Auth::guard('api')->user()->business_id;
    if (!$business) {
      throw new HttpException("Bạn chưa phải là chủ sở hữu hoặc thành viên của một doanh nghiệp !");
    }
    $roles = GroupRole::where('business_id',  $business)->select(["*"]);

    if (isset($request->keyword)) {
      $roles->where(function ($query) use ($request) {
        $query->where('code', 'LIKE', "%$request->keyword%");
        $query->orWhere('title', 'LIKE', "%$request->keyword%");
      });
    }

    $sort_field = "created_at";
    $sort_des = "desc";

    if (isset($request->order_field) && $request->order_field) {
      $sort_field = $request->order_field;
    }

    if (isset($request->order_by) && $request->order_by) {
      $sort_des = $request->order_by;
    }

    if (isset($request->limit) && $request->limit) {
      $limit = $request->limit;
    }

    $roles->orderBy($sort_field, $sort_des);

    if (isset($request->include_id) && $request->include_id) {
      $helper = new Helper();
      $gets =  $roles->get();
      $records = $helper->includeAdapter(
        $gets,
        "id",
        GroupRole::class,
        trim($request->include_id),
        []
      );
      $data = collect($helper->paginateCustom($records))->toArray();
    } else {
      $data = $roles->paginate($limit)->toArray();
    }
    return $data;
  }


  public function find($id)
  {
    $data = GroupRole::with([
      'user_created:id,username,full_name',
      'user_updated:id,username,full_name'
    ])->where("id", $id)->first();
    if (!$data) {
      throw new HttpException("Không tìm thấy dữ liệu !");
    }
    return $data;
  }

  public function create($data)
  {
    try {
      // $checkCode = GroupRole::where('code', trim($data['code']))->first();
      // if ($checkCode) {
      //   throw new HttpException("Mã quyền đã tồn tại !");
      // }
      $business = Auth::guard('api')->user()->business_id;
      if (!$business) {
        throw new HttpException("Bạn chưa phải là chủ sở hữu hoặc thành viên của một doanh nghiệp !");
      }
      $data['business_id'] = $business;
      $data['type'] =  'user';
      $result =  GroupRole::create($data);
      $helperRole = new SyncPermissionRole();
      $helperRole->syncPermissionsBusiness($data['business_id'], [], false);
      return $result;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function createManyOfRow($data)
  {
    try {
      $result = $this->CreateManyRow($data);

      return $result;
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }

  public function update($data)
  {
    try {
      $detail = GroupRole::where('id', $data['id'])->first();
      if (!$detail) {
        throw new HttpException("Không tìm thấy dữ liệu 1");
      }
      // $checkCode = GroupRole::where('code', trim($data['code']))->where('id', '!=', $data['id'])->first();
      // if ($checkCode) {
      //   throw new HttpException("Mã quyền đã tồn tại !");
      // }
      $business = Auth::guard('api')->user()->business_id;
      if (!$business) {
        throw new HttpException("Bạn chưa phải là chủ sở hữu hoặc thành viên của một doanh nghiệp !");
      }
      $data['business_id'] =    $business;
      $detail->update($data);
      return $detail;
    } catch (Exception $e) {
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
      if ($model) {
        return true;
      }
      return false;
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }

  public function delete($id)
  {
    $role_id = null;
    $detail = GroupRole::where('id', $id)->first();
    if (!$detail) {
      throw new HttpException("Không tìm thấy dữ liệu 1");
    }
    $role_id = $detail->id;
    $detail->delete();

    $roleDefault = Role::where('is_default', 1)->whereNull('business_id')->where('type', 'user')->where('code', 'user_default')->first();
    if ($roleDefault && $role_id) {
      User::where('role_id', $role_id)->update(['role_id' => $roleDefault->id]);
    }
    return $detail;
  }
  //
  public function listModule()
  {
    $permissionsUserDefault = [];
    $roleDefault = Role::where('is_default', 1)->whereNull('business_id')->where('type', 'user')->where('code', 'user_default')->first();
    if ($roleDefault) {
      $permissionsUserDefault = RolePermission::where('role_id', $roleDefault->id)->pluck('permission_id')->toArray();
    }
    $business_id = Auth::guard('api')->user()->business_id;
    $user = auth()->guard('api')->user();

    $roleIDUsing = [$user->role_id];

    if (isset(request()->for_business) && request()->for_business) {
      $roleIDUsing = BusinessService::with([
        'service.role'
      ])->where('business_id', $business_id)->where('status', 'is_active')->get()->toArray();
      $roleIDUsing = array_map(function ($item) {
        if (isset($item['role'])) {
          return $item['role']['id'];
        }
      }, array_column($roleIDUsing, 'service'));
    }

    $permissionOfRoles = RolePermission::whereIn('role_id', $roleIDUsing);

    if (isset(request()->for_business) && request()->for_business) {
      $permissionOfRoles->whereNotIn('permission_id', $permissionsUserDefault);
    }

    $permissionOfRoles = $permissionOfRoles->pluck('permission_id')->toArray();

    $idModules = GroupPageControl::whereIn('id', $permissionOfRoles)->pluck('module_id')->unique()->values()->toArray();

    $listModule = Module::whereIn('id', $idModules)->get()->toArray();
    return $listModule;
  }

  public function roleHasPermission($request)
  {
    $list = GroupRolePermission::with("groupRole:id,code,title")->select(['id', 'role_id', 'permission_id'])->where('role_id', $request->role_id)->get();
    return $list;
  }

  public function roleHasPermissionDetail($request)
  {
    $list = GroupRolePermission::with([
      "groupRole:id,code,title",
      "groupControl",
      "groupControl.epic",
      "groupControl.module"
    ])->select(['id', 'role_id', 'permission_id'])->where('role_id', $request->role_id)->get();
    return $list;
  }

  public function listConfigControl($input)
  {
    $pages = Epic::with(['controls']);

    if (isset($input['module_id'])) {
      $pages->where('module_id', $input['module_id']);
    }
    if (isset($input['epic_id'])) {
      $pages->whereHas('pages', function ($query) use ($input) {
        $query->where('epic_id', $input['epic_id']);
      });
    }

    $data = $pages->get();

    return $data;
  }

  public function getPermissionDefault()
  {
    $module = request()->module_id;
    $list = null;
    $business_id = Auth::guard('api')->user()->business_id;
    $getRoleDefault = Role::where('business_id', $business_id)
      ->where('type', 'user')
      ->where('is_default', 1)
      ->first();
    if ($getRoleDefault) {
      $queryData = GroupRolePermission::with([
        "groupRole:id,code,title",
        "groupControl",
        "groupControl.epic",
        "groupControl.module"
      ])->select(['id', 'role_id', 'permission_id'])->where('role_id', $getRoleDefault->id);

      if (isset($module)) {
        $queryData->whereHas("groupControl.module", function ($subQuery) use ($module) {
          $subQuery->where('id', $module);
        });
      }

      $list = $queryData->get();
    }
    return $list;
  }

  public function configPermission($request)
  {
    try {
      $request = $request->all();
      GroupRolePermission::where("role_id", $request['role_id'])->delete();
      if (isset($request['permission_id']) && count($request['permission_id']) > 0) {
        $permissions = $request['permission_id'];
        $dataInsert = [];
        $item = [];
        foreach ($permissions as $key => $value) {
          $checkPermission = GroupRolePermission::where("role_id", $request['role_id'])->where("permission_id", $value)->first();
          if (!$checkPermission) {
            $pageControl = GroupPageControl::where("id", $value)->first();
            if ($pageControl) {
              $item = [
                'role_id' => $request['role_id'],
                'permission_id' => $value,
                'code' => $pageControl->concatenated_code,
              ];
              array_push($dataInsert, $item);
            }
          }
        }
        if (!$dataInsert) {
          throw new DatabaseException('Không tìm thấy quyền cần config');
        }
        foreach ($dataInsert as $key => $value) {
          $result = GroupRolePermission::create($value);
          if (!$result) {
            throw new DatabaseException('Lỗi trong quá trình xử lý');
          }
        }
      }
      return true;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }
}
