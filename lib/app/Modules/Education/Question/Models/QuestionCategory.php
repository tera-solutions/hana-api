<?php

namespace App\Modules\Education\Question\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class QuestionCategory extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_question_categories';

    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(QuestionCategory::class, 'parent_id');
    }
}
