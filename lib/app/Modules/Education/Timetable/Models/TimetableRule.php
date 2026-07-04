<?php

namespace App\Modules\Education\Timetable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A weekly recurrence rule of a timetable (table `edu_timetable_rules`).
 */
class TimetableRule extends Model
{
    protected $table = 'edu_timetable_rules';

    protected $guarded = [];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class, 'timetable_id');
    }
}
