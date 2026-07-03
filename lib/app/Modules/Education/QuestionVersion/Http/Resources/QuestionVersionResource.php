<?php

namespace App\Modules\Education\QuestionVersion\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionVersionResource extends JsonResource
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
            'version' => $this->version,
            'snapshot' => $this->snapshot,
            'change_log' => $this->change_log,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
