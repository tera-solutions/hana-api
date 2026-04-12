<?php

namespace App\Module\Portal\Model;

use App\Module\Portal\Constants\CommonConstants;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_activity_logs';
    protected $guarded = ['id'];
    protected $fillable = [
        'id',
        'object_id',
        'user_id',
        'username',
        'status',
        'type',
        'object_type',
        'action_type',
        'title',
        'url',
        'param',
        'source',
        'content',
        'note',
        'created_at',
        'updated_at',
        'deleted_at',
        'type_sub',
    ];

    protected $appends = ["action_type_text", "object_type_text", "object_sub_type_text"];

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function getActionTypeTextAttribute()
    {
        return CommonConstants::ACTION_TYPE_TEXT[$this->action_type] ?? null;
    }

    public function getObjectTypeTextAttribute()
    {
        return CommonConstants::OBJECT_TEXT[$this->type] ?? null;
    }

    public function getObjectSubTypeTextAttribute()
    {
        return CommonConstants::OBJECT_TEXT[$this->type_sub] ?? null;
    }
}
