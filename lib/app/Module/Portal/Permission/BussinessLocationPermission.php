<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class BussinessLocationPermission extends AbstractPermission  implements PermissionInterface
{

  /**
   * Permission list for collection
   *
   * @var array
   */
  protected $keys =  [
    "list" => "Portal_bussiness_location_list",
    "detail" => "Portal_bussiness_location_detail",
    "create" => "Portal_bussiness_location_create",
    "update" => "Portal_bussiness_location_update",
    "delete" => "Portal_bussiness_location_delete",
  ];

  protected $messages = [
    "Portal_bussiness_location_list" => "Bạn không có quyền xem danh sách cửa hàng!",
    "Portal_bussiness_location_detail" => "Bạn không có quyền xem cửa hàng này!",
    "Portal_bussiness_location_create" => "Bạn không có quyền thêm cửa hàng!",
    "Portal_bussiness_location_update" => "Bạn không có quyền cập nhật cửa hàng!",
    "Portal_bussiness_location_delete" => "Bạn không có quyền xoá cửa hàng!",
  ];
}
