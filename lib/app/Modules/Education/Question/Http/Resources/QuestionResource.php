<?php

namespace App\Modules\Education\Question\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'question_code' => $this->question_code,
            'question_type' => $this->question_type,
            'skill' => $this->skill,
            'difficulty' => $this->difficulty,

            'level_id' => $this->level_id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'category_code' => $this->category->category_code,
                'category_name' => $this->category->category_name,
            ] : null),

            'score' => $this->score,
            'content' => $this->content,
            'explanation' => $this->explanation,

            'cefr_level' => $this->cefr_level,
            'cambridge_level' => $this->cambridge_level,
            'learning_objective' => $this->learning_objective,
            'grammar_topic' => $this->grammar_topic,
            'vocabulary_topic' => $this->vocabulary_topic,

            'version' => $this->version,
            'status' => $this->status,

            'answers_count' => $this->whenCounted('answers'),
            'answers' => QuestionAnswerResource::collection($this->whenLoaded('answers')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('tag_name')),
            'statistic' => $this->whenLoaded('statistic', fn () => $this->statistic ? [
                'usage_count' => $this->statistic->usage_count,
                'correct_count' => $this->statistic->correct_count,
                'incorrect_count' => $this->statistic->incorrect_count,
                'skipped_count' => $this->statistic->skipped_count,
            ] : null),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
