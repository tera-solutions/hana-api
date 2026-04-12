<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class RolePermission extends AbstractPermission  implements PermissionInterface
{

  /**
   * Permission list for collection
   *
   * @var array
   */
  protected $keys =  [
    "list" => "portal_role_list",
    "detail" => "portal_role_detail",
    "create" => "portal_role_create",
    "update" => "portal_role_update",
    "delete" => "portal_role_delete",
  ];

  protected $messages = [
    "portal_role_list" => "Bạn không có quyền xem danh sách quyền!",
    "portal_role_detail" => "Bạn không có quyền xem chi tiết quyền!",
    "portal_role_create" => "Bạn không có quyền tạo quyền!",
    "portal_role_update" => "Bạn không có quyền cập nhật quyền!",
    "portal_role_delete" => "Bạn không có quyền xoá quyền!",
  ];
}
