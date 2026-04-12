<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class CommentPermission extends AbstractPermission  implements PermissionInterface
{

    /**
     * Permission list for collection
     *
     * @var array
     */
    protected $keys =  [
        "list" => "portal_comment_list",
        "detail" => "portal_comment_detail",
        "create" => "portal_comment_create",
        "update" => "portal_comment_update",
        "delete" => "portal_comment_delete",
    ];

    protected $messages = [
        "portal_comment_list" => "Bạn không có quyền xem danh sách bình luận!",
        "portal_comment_detail" => "Bạn không có quyền xem chi tiết bình luận!",
        "portal_comment_create" => "Bạn không có quyền tạo bình luận!",
        "portal_comment_update" => "Bạn không có quyền cập nhật bình luận!",
        "portal_comment_delete" => "Bạn không có quyền xoá bình luận!",
    ];
}
