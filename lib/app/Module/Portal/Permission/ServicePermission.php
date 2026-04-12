<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class ServicePermission extends AbstractPermission  implements PermissionInterface
{

  /**
   * Permission list for collection
   *
   * @var array
   */
  protected $keys =  [
    "list" => "portal_service_list",
    "list_availability" => "portal_service_list_availability",
  ];

  protected $messages = [
    "portal_service_list" => "Bạn không có quyền xem danh sách dịch vụ!",
    "portal_service_list_availability" => "Bạn không có quyền xem danh sách dịch vụ khả dụng!",
  ];
}
