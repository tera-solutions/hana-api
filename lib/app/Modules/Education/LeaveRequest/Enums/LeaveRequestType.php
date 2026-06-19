<?php

namespace App\Modules\Education\LeaveRequest\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum LeaveRequestType: string implements HasLabel
{
    use ProvidesOptions;

    case StudentLeave = 'student_leave';
    case TeacherLeave = 'teacher_leave';

    public function label(): string
    {
        return match ($this) {
            self::StudentLeave => 'Học viên nghỉ học',
            self::TeacherLeave => 'Giáo viên nghỉ dạy',
        };
    }

    /**
     * The requester entity backing each request type.
     */
    public function requesterType(): string
    {
        return match ($this) {
            self::StudentLeave => 'student',
            self::TeacherLeave => 'teacher',
        };
    }
}
