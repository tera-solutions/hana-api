<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class MailPermission extends AbstractPermission  implements PermissionInterface
{

    /**
     * Permission list for collection
     *
     * @var array
     */
    protected $keys =  [
        "list" => "portal_mail_list",
        "detail" => "portal_mail_detail",
    ];

    protected $messages = [
        "portal_mail_list" => "Bạn không có quyền xem danh sách thư!",
        "portal_mail_detail" => "Bạn không có quyền xem chi tiết thư!",

    ];
}
