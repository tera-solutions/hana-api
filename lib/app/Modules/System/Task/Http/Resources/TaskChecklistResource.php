<?php

namespace App\Modules\System\Task\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskChecklistResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'title' => $this->title,
            'is_completed' => $this->is_completed,
            'completed_by' => $this->completed_by,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
