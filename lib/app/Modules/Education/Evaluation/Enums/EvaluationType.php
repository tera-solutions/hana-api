<?php

namespace App\Modules\Education\Evaluation\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum EvaluationType: string implements HasLabel
{
    use ProvidesOptions;

    case Teacher = 'teacher';
    case Student = 'student';
    case Parent = 'parent';

    public function label(): string
    {
        return match ($this) {
            self::Teacher => 'Đánh giá giáo viên',
            self::Student => 'Đánh giá học viên',
            self::Parent => 'Đánh giá phụ huynh',
        };
    }

    /**
     * Allowed criterion keys per evaluation type (evaluation.md §IV).
     *
     * @return string[]
     */
    public function criteria(): array
    {
        return match ($this) {
            self::Teacher => ['expertise', 'teaching_method', 'communication', 'interaction', 'attitude', 'punctuality'],
            self::Student => ['knowledge', 'pronunciation', 'vocabulary', 'grammar', 'communication', 'diligence', 'interaction', 'discipline', 'homework', 'listening', 'speaking', 'reading', 'writing'],
            self::Parent => ['cooperation', 'learning_follow_up', 'on_time_payment', 'meeting_attendance', 'feedback'],
        };
    }
}
