<?php

namespace App\Modules\Education\Exam\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'exam' => $this->whenLoaded('exam', fn () => $this->exam ? [
                'id' => $this->exam->id,
                'exam_code' => $this->exam->exam_code,
                'exam_name' => $this->exam->exam_name,
            ] : null),
            'skill' => $this->skill,
            'question_type' => $this->question_type,
            'content' => $this->content,
            'answer_key' => $this->answer_key,
            'score' => $this->score,
            'difficulty' => $this->difficulty,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
