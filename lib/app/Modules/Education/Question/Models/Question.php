<?php

namespace App\Modules\Education\Question\Models;

use App\Modules\Education\Level\Models\Level;
use App\Modules\Education\Question\Enums\QuestionStatus;
use App\Modules\Education\QuestionVersion\Models\QuestionVersion;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Question extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_questions';

    protected $guarded = [];

    protected $casts = [
        'score' => 'decimal:2',
        'version' => 'integer',
    ];

    public const STATUS_DRAFT = QuestionStatus::Draft->value;

    public const STATUS_REVIEWING = QuestionStatus::Reviewing->value;

    public const STATUS_APPROVED = QuestionStatus::Approved->value;

    public const STATUS_ACTIVE = QuestionStatus::Active->value;

    public const STATUS_ARCHIVED = QuestionStatus::Archived->value;

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuestionAnswer::class, 'question_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(QuestionTag::class, 'edu_question_tag_mappings', 'question_id', 'tag_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class, 'question_id');
    }

    public function statistic(): HasOne
    {
        return $this->hasOne(QuestionStatistic::class, 'question_id');
    }
}
