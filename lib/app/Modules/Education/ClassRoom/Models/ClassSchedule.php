<?php

namespace App\Modules\Education\ClassRoom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSchedule extends Model
{
    protected $table = 'edu_class_schedules';

    protected $guarded = [];

    protected $casts = [
        'weekday' => 'integer',
    ];

    public function eduClass(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }
}
