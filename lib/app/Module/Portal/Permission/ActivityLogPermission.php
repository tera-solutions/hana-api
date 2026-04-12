<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class ActivityLogPermission extends AbstractPermission  implements PermissionInterface
{
    /**
     * Permission list for collection
     *
     * @var array
     */
    protected $keys =  [
        "list" => "portal_activity_list",
    ];

    protected $messages = [
        "portal_activity_list" => "Bạn không có quyền xem nhật ký hoạt động!",
    ];
}