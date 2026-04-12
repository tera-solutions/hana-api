<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class NotificationPermission extends AbstractPermission  implements PermissionInterface
{

    /**
     * Permission list for collection
     *
     * @var array
     */
    protected $keys =  [
        "list" => "portal_notification_list",
        "detail" => "portal_notification_detail",
        "create" => "portal_notification_create",
        "update" => "portal_notification_update",
        "delete" => "portal_notification_delete",
    ];

    protected $messages = [
        "portal_notification_list" => "Bạn không có quyền xem danh sách thông báo!",
        "portal_notification_detail" => "Bạn không có quyền xem chi tiết thông báo!",
        "portal_notification_create" => "Bạn không có quyền tạo thông báo!",
        "portal_notification_update" => "Bạn không có quyền cập nhật thông báo!",
        "portal_notification_delete" => "Bạn không có quyền xoá thông báo!",

    ];
}
