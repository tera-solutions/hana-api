<?php

namespace App\Modules\HR\Teacher\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherCertificateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'certificate_name' => $this->certificate_name,
            'issuer' => $this->issuer,
            'issued_date' => $this->issued_date,
            'expired_date' => $this->expired_date,
            'attachment' => $this->attachment,

            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
