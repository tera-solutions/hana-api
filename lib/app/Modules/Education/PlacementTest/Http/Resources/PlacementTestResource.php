<?php

namespace App\Modules\Education\PlacementTest\Http\Resources;

use App\Modules\Education\Question\Http\Resources\QuestionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PlacementTestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'test_code' => $this->test_code,
            'title' => $this->title,
            'description' => $this->description,
            'cefr_level' => $this->cefr_level,
            'skills' => $this->skills,
            // Kept as the authoritative count (bumped whenever questions are
            // attached — see `PlacementTestService::generateQuestions`), so it
            // stays correct even on the list endpoint, which doesn't load
            // `questions_count` eagerly for every row.
            'question_count' => $this->question_count,
            'questions_count' => $this->whenCounted('questions'),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'teacher_id' => $this->teacher_id,

            'results_count' => $this->whenCounted('results'),
            'stats' => $this->when(isset($this->stats), fn () => $this->stats),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
