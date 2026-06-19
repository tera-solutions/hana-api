<?php

namespace App\Modules\Education\Question\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionAnswerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'answer_key' => $this->answer_key,
            'answer_content' => $this->answer_content,
            'is_correct' => $this->is_correct,
            'sort_order' => $this->sort_order,
        ];
    }
}
