<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Option extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */


    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    protected $appends = ['image_url'];

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getImageUrlAttribute()
    {
        if (!empty($this->value)) {
            $image_url = asset($this->value);
        } else {
            $image_url = asset('/assets/default.png');
        }
        return $image_url;
    }
}
