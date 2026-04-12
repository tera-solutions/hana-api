<?php

namespace App\Module\Portal\Model;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationUser extends Model
{
    protected $guarded = ['id'];
    protected $table = "sys_notification_users";

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function notification()
    {
        return $this->belongsTo(\App\Models\Notification::class, 'notification_id');
    }
}
