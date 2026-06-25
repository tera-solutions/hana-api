<?php

namespace App\Modules\Education\LeaveRequest\Http\Resources;

use App\Modules\Education\LeaveRequest\Enums\LeaveReasonType;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'request_code' => $this->request_code,
            'request_type' => $this->request_type,
            'requester_type' => $this->requester_type,
            'requester_id' => $this->requester_id,

            'class_room_id' => $this->class_room_id,
            'class_room' => $this->whenLoaded('classRoom', fn () => $this->classRoom ? [
                'id' => $this->classRoom->id,
            ] : null),

            'lesson_id' => $this->lesson_id,
            'lesson' => $this->whenLoaded('lesson', fn () => $this->lesson ? [
                'id' => $this->lesson->id,
                'lesson_no' => $this->lesson->lesson_no,
                'lesson_title' => $this->lesson->lesson_title,
                'lesson_date' => $this->lesson->lesson_date,
                'status' => $this->lesson->status,
            ] : null),

            'leave_date' => $this->leave_date,
            'reason_type' => $this->reason_type,
            'reason_type_label' => LeaveReasonType::tryFrom((string) $this->reason_type)?->label(),
            'reason' => $this->reason,
            'attachment_file_id' => $this->attachment_file_id,

            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'rejection_reason' => $this->rejection_reason,

            'makeups' => MakeupLessonResource::collection($this->whenLoaded('makeups')),
            'logs' => $this->whenLoaded('logs'),

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
