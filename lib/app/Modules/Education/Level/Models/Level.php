<?php

namespace App\Modules\Education\Level\Models;

use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\StudentLevel\Models\StudentLevel;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use LogsActivity;

    protected $table = 'edu_levels';

    protected $guarded = [];

    protected $casts = [
        'course_id' => 'integer',
        'level_order' => 'integer',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function studentLevels(): HasMany
    {
        return $this->hasMany(StudentLevel::class, 'level_id');
    }
}
