<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceCountAdmin extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */

    protected $connection = 'admin';

    protected $table = 'reference_counts';
    protected $guarded = ['id'];

    protected $fillable = [
        'ref_type',
        'ref_count',
        'business_id',
        'created_at',
        'updated_at'
    ];
}
