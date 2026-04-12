<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class BusinessPermission extends AbstractPermission  implements PermissionInterface
{
  /**
   * Permission list for collection
   *
   * @var array
   */
  protected $keys =  [
    "list" => "portal_business_list",
    "detail" => "portal_business_detail",
    "create" => "portal_business_create",
    "update" => "portal_business_update",
    "delete" => "portal_business_delete",
  ];

  protected $messages = [
    "portal_business_list" => "Bạn không có quyền xem doanh nghiệp!",
    "portal_business_detail" => "Bạn không có quyền xem chi tiết doanh nghiệp!",
    "portal_business_create" => "Bạn không có quyền tạo doanh nghiệp!",
    "portal_business_update" => "Bạn không có quyền cập nhật doanh nghiệp!",
    "portal_business_delete" => "Bạn không có quyền xoá doanh nghiệp!",
  ];
}
