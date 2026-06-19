<?php

namespace App\Modules\Education\Question\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionStatistic extends Model
{
    protected $table = 'edu_question_statistics';

    protected $guarded = [];

    protected $casts = [
        'usage_count' => 'integer',
        'correct_count' => 'integer',
        'incorrect_count' => 'integer',
        'skipped_count' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
