<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class AttachmentPermission extends AbstractPermission  implements PermissionInterface
{
    /**
     * Permission list for collection
     *
     * @var array
     */
    protected $keys =  [
        "list" => "portal_attachment_list",
        "detail" => "portal_attachment_detail",
        "create" => "portal_attachment_create",
        "update" => "portal_attachment_update",
        "delete" => "portal_attachment_delete",
    ];

    protected $messages = [
        "portal_attachment_list" => "Bạn không có quyền xem tập tin đính kèm!",
        "portal_attachment_detail" => "Bạn không có quyền xem chi tiết tập tin đính kèm!",
        "portal_attachment_create" => "Bạn không có quyền tạo tập tin đính kèm!",
        "portal_attachment_update" => "Bạn không có quyền cập nhật tập tin đính kèm!",
        "portal_attachment_delete" => "Bạn không có quyền xoá tập tin đính kèm!",
    ];
}
