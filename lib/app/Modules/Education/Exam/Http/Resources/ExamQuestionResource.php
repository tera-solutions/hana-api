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
            'file_id' => $this->file_id,
            'file_name' => $this->file_name,
            'file_url' => $this->whenLoaded('file', fn () => $this->file?->file_url),
            'score' => $this->score,
            'difficulty' => $this->difficulty,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
