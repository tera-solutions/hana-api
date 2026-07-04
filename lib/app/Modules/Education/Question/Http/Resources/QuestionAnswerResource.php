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
            'question' => $this->whenLoaded('question', fn () => $this->question ? [
                'id' => $this->question->id,
                'question_code' => $this->question->question_code,
                'content' => $this->question->content,
            ] : null),
            'answer_key' => $this->answer_key,
            'answer_content' => $this->answer_content,
            'is_correct' => $this->is_correct,
            'sort_order' => $this->sort_order,
        ];
    }
}
