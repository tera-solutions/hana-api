<?php

namespace App\Modules\Education\Question\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionTagResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tag_name' => $this->tag_name,
            'questions_count' => $this->whenCounted('questions'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
