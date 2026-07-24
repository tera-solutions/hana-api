<?php

namespace App\Modules\Education\Certificate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'name' => $this->student->name,
            ] : null),
            'course' => $this->whenLoaded('course', fn () => $this->course?->name),
            'class' => $this->whenLoaded('classRoom', fn () => $this->classRoom?->name),
            'template' => $this->whenLoaded('template', fn () => $this->template?->name),
            'issued_at' => $this->issued_at,
            'status' => $this->status,
            'final_score' => $this->final_score,
        ];
    }
}
