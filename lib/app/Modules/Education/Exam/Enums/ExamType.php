<?php

namespace App\Modules\Education\Exam\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ExamType: string implements HasLabel
{
    use ProvidesOptions;

    case Placement = 'placement';
    case Progress = 'progress';
    case Midterm = 'midterm';
    case Final = 'final';
    case Promotion = 'promotion';
    case MockTest = 'mock_test';
    case Certification = 'certification';

    public function label(): string
    {
        return match ($this) {
            self::Placement => 'Kiểm tra đầu vào',
            self::Progress => 'Kiểm tra định kỳ',
            self::Midterm => 'Giữa khóa',
            self::Final => 'Cuối khóa',
            self::Promotion => 'Xét lên cấp',
            self::MockTest => 'Thi thử',
            self::Certification => 'Thi chứng chỉ',
        };
    }
}
