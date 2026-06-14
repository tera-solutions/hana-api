<?php

namespace App\Support\Metadata;

use App\Enums\Finance\DebtStatus;
use App\Enums\Finance\InvoiceStatus;
use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\RefundStatus;
use App\Enums\Shared\Gender;
use App\Enums\Shared\GuardianRelation;
use App\Modules\CRM\Lead\Enums\LeadStatus;
use App\Modules\CRM\Parent\Enums\ParentStatus;
use App\Modules\Education\Attendance\Enums\AttendanceStatus;
use App\Modules\Education\ClassRoom\Enums\ClassLearningType;
use App\Modules\Education\ClassRoom\Enums\ClassStatus;
use App\Modules\Education\ClassRoom\Enums\ClassStudentStatus;
use App\Modules\Education\ClassSession\Enums\ClassSessionStatus;
use App\Modules\Education\Enrollment\Enums\EnrollmentStatus;
use App\Modules\Education\Student\Enums\StudentStatus;
use App\Modules\HR\Teacher\Enums\TeacherStatus;
use App\Modules\HR\Teacher\Enums\TeacherType;
use App\Modules\System\Branch\Enums\BranchStatus;
use App\Modules\System\Business\Enums\BusinessStatus;
use App\Modules\System\User\Enums\UserStatus;

/**
 * Single source of truth for the enumerations the frontend needs to render
 * dropdowns, status badges and filters. Grouped by domain; every leaf is a list
 * of `{ value, label }` produced from a backed enum, so there is exactly one
 * place to add or rename an option.
 */
class MetadataRegistry
{
    /**
     * The full catalog: `domain => field => [{value, label}, ...]`.
     *
     * @return array<string, array<string, array<int, array{value: int|string, label: string}>>>
     */
    public static function all(): array
    {
        return [
            'shared' => [
                'gender' => Gender::options(),
                'guardian_relation' => GuardianRelation::options(),
            ],
            'system' => [
                'user_status' => UserStatus::options(),
                'business_status' => BusinessStatus::options(),
                'branch_status' => BranchStatus::options(),
            ],
            'crm' => [
                'lead_status' => LeadStatus::options(),
                'parent_status' => ParentStatus::options(),
            ],
            'education' => [
                'student_status' => StudentStatus::options(),
                'class_status' => ClassStatus::options(),
                'class_learning_type' => ClassLearningType::options(),
                'class_student_status' => ClassStudentStatus::options(),
                'class_session_status' => ClassSessionStatus::options(),
                'attendance_status' => AttendanceStatus::options(),
                'enrollment_status' => EnrollmentStatus::options(),
            ],
            'hr' => [
                'teacher_status' => TeacherStatus::options(),
                'teacher_type' => TeacherType::options(),
            ],
            'finance' => [
                'invoice_status' => InvoiceStatus::options(),
                'payment_status' => PaymentStatus::options(),
                'payment_method' => PaymentMethod::options(),
                'debt_status' => DebtStatus::options(),
                'refund_status' => RefundStatus::options(),
            ],
        ];
    }
}
