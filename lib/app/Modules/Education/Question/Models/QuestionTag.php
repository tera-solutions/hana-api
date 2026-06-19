<?php

namespace App\Modules\Education\Question\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuestionTag extends Model
{
    protected $table = 'edu_question_tags';

    protected $guarded = [];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'edu_question_tag_mappings', 'tag_id', 'question_id');
    }
}
