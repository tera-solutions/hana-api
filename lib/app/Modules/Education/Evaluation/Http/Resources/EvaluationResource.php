<?php

namespace App\Modules\Education\Evaluation\Http\Resources;

use App\Modules\Education\Evaluation\Enums\EvaluationClassification;
use App\Modules\Education\Evaluation\Enums\EvaluationPeriod;
use App\Modules\Education\Evaluation\Enums\EvaluationStatus;
use App\Modules\Education\Evaluation\Enums\EvaluationType;
use App\Modules\Education\Evaluation\Enums\EvaluatorType;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'evaluation_code' => $this->evaluation_code,

            'evaluation_type' => $this->evaluation_type,
            'evaluation_type_label' => EvaluationType::tryFrom((string) $this->evaluation_type)?->label(),
            'target_id' => $this->target_id,

            'evaluator_type' => $this->evaluator_type,
            'evaluator_type_label' => EvaluatorType::tryFrom((string) $this->evaluator_type)?->label(),
            'evaluator_id' => $this->evaluator_id,

            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => $this->course ? [
                'id' => $this->course->id,
                'name' => $this->course->name,
            ] : null),

            'class_room_id' => $this->class_room_id,
            'class_room' => $this->whenLoaded('classRoom', fn () => $this->classRoom ? [
                'id' => $this->classRoom->id,
                'name' => $this->classRoom->name,
            ] : null),

            'lesson_id' => $this->lesson_id,
            'lesson' => $this->whenLoaded('lesson', fn () => $this->lesson ? [
                'id' => $this->lesson->id,
                'lesson_no' => $this->lesson->lesson_no,
                'lesson_title' => $this->lesson->lesson_title,
            ] : null),

            'evaluation_period' => $this->evaluation_period,
            'evaluation_period_label' => EvaluationPeriod::tryFrom((string) $this->evaluation_period)?->label(),

            'criteria' => $this->criteria,
            'score' => $this->score,
            'classification' => $this->classification,
            'classification_label' => EvaluationClassification::tryFrom((string) $this->classification)?->label(),

            'comment' => $this->comment,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'recommendations' => $this->recommendations,

            'status' => $this->status,
            'status_label' => EvaluationStatus::tryFrom((string) $this->status)?->label(),
            'evaluated_at' => $this->evaluated_at,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
