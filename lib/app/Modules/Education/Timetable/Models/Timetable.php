<?php

namespace App\Modules\Education\Timetable\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Room\Models\Room;
use App\Modules\Education\Timetable\Enums\TimetableStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * A class schedule that generates sessions (table `edu_timetables`).
 */
class Timetable extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'edu_timetables';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_sessions' => 'integer',
    ];

    public const STATUS_DRAFT = TimetableStatus::Draft->value;

    public const STATUS_ACTIVE = TimetableStatus::Active->value;

    public const STATUS_COMPLETED = TimetableStatus::Completed->value;

    public const STATUS_CANCELLED = TimetableStatus::Cancelled->value;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TimetableRule::class, 'timetable_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(TimetableChange::class, 'timetable_id')->latest('id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'timetable_id');
    }
}
