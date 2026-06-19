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
            'version' => $this->version,
            'snapshot' => $this->snapshot,
            'change_log' => $this->change_log,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
