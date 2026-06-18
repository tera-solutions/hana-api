<?php

namespace App\Modules\Education\Assignment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentSubmissionFileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'file_id' => $this->file_id,
            'file_name' => $this->file_name,
        ];
    }
}
