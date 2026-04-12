<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class MemberPermission extends AbstractPermission  implements PermissionInterface
{

  /**
   * Permission list for collection
   *
   * @var array
   */
  protected $keys =  [
    "list" => "portal_member_list",
    "detail" => "portal_member_detail",
    "create" => "portal_member_create",
    "update" => "portal_member_update",
    "delete" => "portal_member_delete",
  ];

  protected $messages = [
    "portal_member_list" => "Bạn không có quyền xem danh sách thành viên!",
    "portal_member_detail" => "Bạn không có quyền xem chi tiết thành viên!",
    "portal_member_create" => "Bạn không có quyền tạo thành viên!",
    "portal_member_update" => "Bạn không có quyền cập nhật thành viên!",
    "portal_member_delete" => "Bạn không có quyền xoá thành viên!",
  ];
}
