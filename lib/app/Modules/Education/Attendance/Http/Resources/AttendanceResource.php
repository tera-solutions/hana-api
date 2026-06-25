<?php

namespace App\Modules\Education\Attendance\Http\Resources;

use App\Modules\Education\Attendance\Enums\AttendanceStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'session_id' => $this->session_id,
            'session' => $this->whenLoaded('session', fn () => $this->session ? [
                'id' => $this->session->id,
                'session_no' => $this->session->session_no,
                'name' => $this->session->name,
                'session_date' => $this->session->session_date,
                'status' => $this->session->status,
            ] : null),

            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'code' => $this->student->code,
                'name' => $this->student->name,
            ] : null),

            'status' => $this->status,
            'status_label' => AttendanceStatus::tryFrom((string) $this->status)?->label(),
            'checkin_time' => $this->checkin_time,
            'checkout_time' => $this->checkout_time,
            'note' => $this->note,

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
