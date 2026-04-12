<?php

namespace App\Module\Portal\Model;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    protected $guarded = ['id'];

    protected $table = "sys_comments";

    protected $fillable = [
        "id",
        "object_id",
        "parent_id",
        "content",
        "type",
        "title",
        "count_like",
        "allow_reply",
        "created_by",
        "is_delete",
        "created_at",
        "updated_at"
    ];

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function media()
    {
        return $this->belongsTo(\App\Module\Portal\Model\Media::class, 'media_id');
    }

    public function children()
    {
        return $this->hasMany(\App\Module\Portal\Model\Comment::class, 'parent_id');
    }
}
