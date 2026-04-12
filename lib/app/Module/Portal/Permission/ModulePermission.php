<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class ModulePermission extends AbstractPermission  implements PermissionInterface
{

    /**
     * Permission list for collection
     *
     * @var array
     */
    protected $keys =  [
        "list" => "Portal_module_list",
        "detail" => "Portal_module_detail",
        "create" => "Portal_module_create",
        "update" => "Portal_module_update",
        "delete" => "Portal_module_delete",

    ];

    protected $messages = [
        "Portal_module_list" => "Bạn không có quyền xem danh sách chứng chỉ!",
        "Portal_module_detail" => "Bạn không có quyền xem chứng chỉ này!",
        "Portal_module_create" => "Bạn không có quyền thêm chứng chỉ!",
        "Portal_module_update" => "Bạn không có quyền cập nhật chứng chỉ!",
        "Portal_module_delete" => "Bạn không có quyền xoá chứng chỉ!",
    ];
}
