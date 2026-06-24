<?php

namespace App\Modules\Education\Evaluation\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Evaluation\Enums\EvaluationClassification;
use App\Modules\Education\Evaluation\Enums\EvaluationStatus;
use App\Modules\Education\Evaluation\Enums\EvaluationType;
use App\Modules\Education\Lesson\Models\Lesson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * An evaluation of a teacher / student / parent (table `edu_evaluations`).
 * Polymorphic by `evaluation_type`: `target_id` and `evaluator_id` are typed ids.
 */
class Evaluation extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'edu_evaluations';

    protected $guarded = [];

    protected $casts = [
        'criteria' => 'array',
        'score' => 'decimal:2',
        'evaluated_at' => 'datetime',
    ];

    public const TYPE_TEACHER = EvaluationType::Teacher->value;

    public const TYPE_STUDENT = EvaluationType::Student->value;

    public const TYPE_PARENT = EvaluationType::Parent->value;

    public const STATUS_DRAFT = EvaluationStatus::Draft->value;

    public const STATUS_SUBMITTED = EvaluationStatus::Submitted->value;

    public const STATUS_APPROVED = EvaluationStatus::Approved->value;

    public const STATUS_REJECTED = EvaluationStatus::Rejected->value;

    public const STATUS_LOCKED = EvaluationStatus::Locked->value;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function classificationLabel(): ?string
    {
        return EvaluationClassification::tryFrom((string) $this->classification)?->label();
    }
}
