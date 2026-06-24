<?php

namespace App\Modules\System\Task\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskAttachmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'file_id' => $this->file_id,
            'file' => $this->whenLoaded('file', fn () => $this->file ? [
                'id' => $this->file->id,
                'file_name' => $this->file->file_name,
                'file_path' => $this->file->file_path,
                'file_type' => $this->file->file_type,
            ] : null),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
