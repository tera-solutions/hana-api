<?php

namespace App\Helpers;

use App\Models\Business;
use App\Models\BusinessService;
use App\Models\RolePermission;
use App\Models\Role as AppModelRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupPageControl;
use Package\Exception\HttpException;

class SyncPermissionRole
{
  public $permissions = [];
  public $roleIndividualUser;
  public $permissionIndividualUser = [];
  protected $roleUsingID = [];
  public function __construct()
  {
    $this->roleIndividualUser = AppModelRole::whereNull('business_id')
      ->where('type', 'user')
      ->where('is_default', 1)
      ->first();
    if ($this->roleIndividualUser) {
      $this->permissionIndividualUser = RolePermission::where('role_id', $this->roleIndividualUser->id)->get()->toArray();
    }
  }

  public function getPermissions(array $input)
  {
    $response = [];
    $business_id = 0;
    $roleMember = [];
    $roleBusinessDefault = null;
    $isRoleBusiness = null;
    if (isset($input['business_id'])) {
      if ($input['business_id']) {
        $business_id = $input['business_id'];
        $roleBusinessDefault = AppModelRole::where('business_id',  $business_id)
          ->where('type', 'user')
          ->where('is_default', 1)
          ->pluck('id')
          ->first();
      }
    } else {
      if (isset($input['user_id'])) {
        $user = User::where('id', $input['user_id'])->first();
        if ($user) {
          $roleBusinessDefault = $user->role_id;
          $isRoleMember = $this->isRoleMember($user->role_id, false);
          if ($user->business_id) {
            if ($user->type == 'owner') {
              if ($isRoleMember) {
                $roleMember[] = $isRoleMember->id;
              } else {
                $business_id = $user->business_id;
              }
            } elseif ($user->type == 'member') {
              if ($isRoleMember) {
                $roleMember[] = $isRoleMember->id;
              } else {
                $business_id = $user->business_id;
              }
            }
          }
        }
      } else {
        if (Auth::guard('api')->user()->type != 'owner') {
          throw new HttpException("Tài khoản không phải là chủ sở hữu thì không được dùng chức năng này !");
        }
        $business_id = Auth::guard('api')->user()->business_id;
      }
    }

    if ($roleBusinessDefault) {
      $isRoleBusiness = $this->isRoleMember($roleBusinessDefault);
    }

    $roleIDUsing = BusinessService::with([
      'service.role'
    ])->where('business_id', $business_id)
      ->where('status', 'is_active')
      ->get()
      ->toArray();
    $roleIDUsing = array_map(function ($item) {
      if (isset($item['role'])) {
        return $item['role']['id'];
      }
    }, array_column($roleIDUsing, 'service'));

    if (!empty($roleMember)) {
      $roleIDUsing = array_merge($roleMember, $roleIDUsing);
    }

    if (isset($input['with_default'])) {
      if ($input['with_default'] == 1) {
        if ($isRoleBusiness && $this->roleIndividualUser) {
          $roleIDUsing = array_merge([$this->roleIndividualUser->id], $roleIDUsing);
        }
      }
    }
    // role default
    if (isset($input['user_id'])) {
      $user = User::where('id', $input['user_id'])->first();
      if ($user) {
        if ($user->type == 'owner' || $user->type == 'member') {
          $roleUserDefault = AppModelRole::where('type', 'user')
            ->where('code', 'user_default')
            ->whereNull('business_id')
            ->first();
          if ($roleUserDefault) {
            $roleIDUsing =  array_merge($roleIDUsing, [$roleUserDefault->id]);
          }
        } else {
          $roleIDUsing = [$user->role_id];
        }
      }
    }
    //
    $this->roleUsingID = $roleIDUsing;
    //
    $roleHasPermission = RolePermission::whereIn('role_id', $roleIDUsing)
      ->pluck('permission_id')
      ->toArray();

    $getGroupPageControl = GroupPageControl::with(['module:id,title', 'epic:id,name'])
      ->whereIn('id', $roleHasPermission)
      ->get()
      ->toArray();
    foreach ($getGroupPageControl as $item) {
      $moduleId = $item['module_id'];
      $epicId = $item['epic_id'];
      if ($moduleId && $epicId) {
        if (!isset($response[$moduleId])) {
          $response[$moduleId] = [
            'module_id' => $moduleId,
            'module_name' => $item['module']['title'],
            'epics' => []
          ];
        }
        if (!isset($response[$moduleId]['epics'][$epicId])) {
          $response[$moduleId]['epics'][$epicId] = [
            'id' => $epicId,
            'name' => isset($item['epic']) ? $item['epic']['name'] : null,
            'controls' => []
          ];
        }
        $response[$moduleId]['epics'][$epicId]['controls'][] = [
          'id' => $item['id'],
          'concatenated_code' => $item['concatenated_code'],
          'title' => $item['title'],
        ];
      }
    }
    $response = array_values($response);
    foreach ($response as &$module) {
      $module['epics'] = array_values($module['epics']);
      foreach ($module['epics'] as &$epic) {
        $epic['controls'] = array_values($epic['controls']);
      }
    }
    $response = array_values($response);
    if (isset($input['module_id'])) {
      if ($input['module_id']) {
        $response = array_filter($response, function ($item) use ($input) {
          return $item['module_id'] == $input['module_id'];
        });
        list($module) = array_values($response);
        $response = $module;
      }
    }
    $this->permissions = $response;
    return $this;
  }

  public function isRoleMember($roleId, $checkIsRoleDefault = true)
  {
    $role = AppModelRole::where('id', $roleId)
      ->where('type', 'user');
    if ($checkIsRoleDefault) {
      $role->where('is_default', 1);
    } else {
      $role->where('is_default', 0);
    }
    return $role->first();
  }

  public function extractControlIds(array $arr): array
  {
    $epics = [];
    $controls = [];
    // Dùng array_map để lấy danh sách các epics
    $epicsList = array_map(function ($module) {
      return $module['epics'] ?? [];
    }, $arr);

    // Gộp tất cả các epics vào một mảng duy nhất
    if (!empty($epicsList)) {
      $epics = array_merge(...$epicsList);
    }

    // Dùng array_map để lấy danh sách các controls từ các epics
    $controlsList = array_map(function ($epic) {
      return $epic['controls'] ?? [];
    }, $epics);

    // Gộp tất cả các controls vào một mảng duy nhất
    if (!empty($controlsList)) {
      $controls = array_merge(...$controlsList);
    }

    // Lấy tất cả các ID từ controls và trả về chúng
    $ids = array_map(function ($control) {
      return $control['id'];
    }, $controls);

    return $ids;
  }

  public function sync($roleIds, $atMidnight = true, $roleAddition = [])
  {
    $arrayReset = $this->extractControlIds($this->permissions);
    $roleDefault = AppModelRole::whereIn('id', $roleIds)
      ->where('is_default', 1)
      ->first();
    if (empty($this->permissions)) {
      // Nếu không có permission nào có nghĩa là doanh nghiệp đó ko có quyền nào nên xóa hết.
      RolePermission::whereIn('role_id', $roleIds)->whereNotIn('permission_id', array_column($this->permissionIndividualUser, 'permission_id'))->delete();
      return;
    }
    $rolesNotDefault = array_diff($roleIds, [$roleDefault ? $roleDefault->id : null]);

    if ($atMidnight) {
      if ($roleDefault) {
        RolePermission::where('role_id', $roleDefault->id)->delete();
      }
    } else {
      if (!empty($roleAddition)) {
        $arrayReset = array_merge(
          $arrayReset,
          RolePermission::whereIn('role_id', $roleAddition)->pluck('permission_id')->toArray()
        );
      }
      //
      if ($roleDefault) {
        RolePermission::where('role_id', $roleDefault->id)
          ->whereIn(
            'permission_id',
            $arrayReset
          )
          ->delete();
      }
    }
    if (!empty($rolesNotDefault)) {
      RolePermission::whereIn('role_id', $rolesNotDefault)
        ->whereNotIn(
          'permission_id',
          $arrayReset
        )->delete();
      $roleGroupBy = collect(RolePermission::whereIn('role_id', $rolesNotDefault)->get())
        ->groupBy('role_id')
        ->toArray();
      $dataInsertRoleNotDefault = [];
      foreach ($rolesNotDefault as $key => $value) {
        $arrayPush = [];
        $arrayToMap = [];
        if (!isset($roleGroupBy[$value])) {
          $arrayToMap = $this->permissionIndividualUser;
        } else {
          $this->checkAndCreatePermissionDefaultExist($roleGroupBy[$value], $this->permissionIndividualUser);
          $arrayToMap =  $roleGroupBy[$value];
        }

        $arrayPush = array_map(function ($item) use ($value) {
          return [
            'role_id' => $value,
            'code' => $item['code'],
            'permission_id' => $item['permission_id'],
            'created_at' => now()->format('Y-m-d')
          ];
        }, $arrayToMap);
        $dataInsertRoleNotDefault[] =    $arrayPush;
      }
      $dataInsertRoleNotDefault = collect($dataInsertRoleNotDefault)->collapse()->toArray();
      RolePermission::whereIn('role_id', $rolesNotDefault)->delete();
      if (!empty($dataInsertRoleNotDefault)) {
        RolePermission::insert($dataInsertRoleNotDefault);
      }
      //
    }
    // Tạo một mảng để lưu trữ dữ liệu chèn hàng loạt
    $dataInsert = [];

    // Sử dụng `flatMap` từ Laravel Collection để làm phẳng dữ liệu và xử lý nó
    if ($roleDefault) {
      $dataInsert = collect($this->permissions)
        ->flatMap(function ($permission) {
          return collect($permission['epics'] ?? [])
            ->flatMap(function ($epic) {
              return collect($epic['controls'] ?? [])
                ->map(function ($control) {
                  return [
                    'permission_id' => $control['id'],
                    'code' => $control['concatenated_code'],
                    'created_at' => now(),
                  ];
                });
            });
        })
        ->map(function ($data) use ($roleDefault) {
          $data['role_id'] = $roleDefault->id;
          return $data;
        })
        ->all();
    }

    // Thực hiện chèn dữ liệu hàng loạt
    if (!empty($dataInsert)) {
      RolePermission::insert($dataInsert);
    }
  }

  public function checkAndCreatePermissionDefaultExist(&$a, $b)
  {

    // Lặp qua mảng $b
    foreach ($b as $itemB) {
      // Kiểm tra nếu permission_id không tồn tại trong mảng $a
      $exist = false;
      foreach ($a as $itemA) {
        if ($itemA['permission_id'] == $itemB['permission_id']) {
          $exist = true;
          break;
        }
      }

      // Nếu không tồn tại, thêm phần tử từ $b vào $a
      if (!$exist) {
        $a[] = $itemB;
      }
    }
  }

  public function syncPermissions($business_id, $roleIds, $roleAddition = [], $atMidnight = true)
  {
    $this->getPermissions(['business_id' => $business_id, 'with_default' => 1])
      ->sync($roleIds, $atMidnight, $roleAddition);
  }

  public function syncPermissionsBusiness($business_id, $roleAddition = [], $atMidnight = true)
  {
    $business = Business::with(['roles'])
      ->where('id', $business_id)
      ->first();
    if ($business) {
      if (!empty($business->roles)) {
        $this->syncPermissions(
          $business_id,
          array_column(
            collect($business->roles)
              ->toArray(),
            'id'
          ),
          $roleAddition,
          $atMidnight
        );
      }
    }
  }
}
