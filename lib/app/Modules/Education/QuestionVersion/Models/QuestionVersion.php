<?php

namespace App\Modules\Education\QuestionVersion\Models;

use App\Modules\Education\Question\Models\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

class QuestionVersion extends Model
{
    use HasAuditFields;

    protected $table = 'edu_question_versions';

    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
        'version' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
