<?php

namespace App\Modules\Education\Timetable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An audit record of a schedule change (table `edu_timetable_changes`).
 */
class TimetableChange extends Model
{
    protected $table = 'edu_timetable_changes';

    protected $guarded = [];

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class, 'timetable_id');
    }
}
