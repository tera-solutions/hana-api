<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class BillPermission extends AbstractPermission  implements PermissionInterface
{

  /**
   * Permission list for collection
   *
   * @var array
   */
  protected $keys =  [
    "list" => "portal_cart_list",
    "create" => "portal_cart_create",
    "update" => "portal_cart_update",
    "delete" => "portal_cart_delete",
    "replace" => "portal_cart_replace",
  ];

  protected $messages = [
    "portal_cart_list" => "Bạn không có quyền xem danh sách giỏ hàng!",
    "portal_cart_create" => "Bạn không có quyền xem chi tiết giỏ hàng!",
    "portal_cart_update" => "Bạn không có quyền tạo giỏ hàng!",
    "portal_cart_delete" => "Bạn không có quyền cập nhật giỏ hàng!",
    "portal_cart_replace" => "Bạn không có quyền xoá giỏ hàng!",
  ];
}
