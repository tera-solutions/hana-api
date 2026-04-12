<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $appends = ['file_url', 'media_id'];

    public function getFileUrlAttribute()
    {
        if (!empty($this->file_path)) {
            $file_url = asset($this->file_path);
        } else {
            $file_url = asset('/assets/default.png');
        }

        return $file_url;
    }

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    public function getMediaIdAttribute()
    {
        $media_id = null;
        if (!empty($this->link_file)) {
            $media_id = 1;
        }

        if (!empty($this->media_id)) {
            $media_id = $this->media_id;
        }

        return $media_id;
    }
}
