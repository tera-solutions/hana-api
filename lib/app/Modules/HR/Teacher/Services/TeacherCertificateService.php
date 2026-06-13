<?php

namespace App\Modules\HR\Teacher\Services;

use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\HR\Teacher\Models\TeacherCertificate;

class TeacherCertificateService
{
    /**
     * List a teacher's certificates (newest expiry first).
     */
    public function listFor($teacherId)
    {
        Teacher::findOrFail($teacherId);

        return TeacherCertificate::where('teacher_id', $teacherId)
            ->orderByRaw('expired_date IS NULL, expired_date asc')
            ->get();
    }

    public function create($teacherId, array $data)
    {
        Teacher::findOrFail($teacherId);

        $data['teacher_id'] = $teacherId;

        return TeacherCertificate::create($data);
    }

    public function update($id, array $data)
    {
        $certificate = TeacherCertificate::findOrFail($id);

        unset($data['id'], $data['teacher_id']);

        $certificate->update($data);

        return $certificate;
    }

    public function delete($id)
    {
        return TeacherCertificate::findOrFail($id)->delete();
    }
}
