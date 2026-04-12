<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class UserPermission extends AbstractPermission  implements PermissionInterface
{

    /**
     * Permission list for collection
     *
     * @var array
     */
    protected $keys =  [
        "list" => "portal_user_list",
        "detail" => "portal_user_detail",
        "create" => "portal_user_create",
        "update" => "portal_user_update",
        "delete" => "portal_user_delete",
    ];

    protected $messages = [
        "portal_user_list" => "Bạn không có quyền xem danh sách người dùng!",
        "portal_user_detail" => "Bạn không có quyền xem chi tiết người dùng!",
        "portal_user_create" => "Bạn không có quyền tạo người dùng!",
        "portal_user_update" => "Bạn không có quyền cập nhật người dùng!",
        "portal_user_delete" => "Bạn không có quyền xoá người dùng!",

    ];
}
