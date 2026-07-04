<?php

namespace App\Modules\System\Task\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskCommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
        ];
    }
}
