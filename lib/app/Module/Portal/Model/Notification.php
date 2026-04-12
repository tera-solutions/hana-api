<?php

namespace App\Module\Portal\Model;

use App\Models\User;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    protected $guarded = ['id'];

    protected $table = "sys_notifications";

    protected $fillable = [
        "user_id",
        "object_id",
        "object_type",
        "is_view",
        "title",
        "content",
        "created_at",
        "updated_at",
        "parent_id",
        "created_by",
        "is_delete"
    ];

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function users()
    {
        return $this->hasMany(\App\Module\Portal\Model\NotificationUser::class, 'notification_id');
    }

    public function usersSeen()
    {
        return $this->belongsToMany(\App\Models\User::class, "sys_notification_users", "notification_id", "user_id");
    }

    public function children()
    {
        return $this->hasMany(\App\Module\Portal\Model\Notification::class, 'parent_id');
    }

    public function media()
    {
        return $this->hasMany(\App\Models\Media::class, 'object_id')->where("object_type", "mail");
    }
}
