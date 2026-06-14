<?php

namespace App\Modules\Education\ClassSchedule\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
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
