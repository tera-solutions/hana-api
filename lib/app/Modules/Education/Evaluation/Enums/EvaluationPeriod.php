<?php

namespace App\Modules\Education\Evaluation\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum EvaluationPeriod: string implements HasLabel
{
    use ProvidesOptions;

    case Session = 'session';
    case Lesson = 'lesson';
    case Midterm = 'midterm';
    case Final = 'final';
    case Course = 'course';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    public function label(): string
    {
        return match ($this) {
            self::Session => 'Sau buổi học',
            self::Lesson => 'Sau bài học',
            self::Midterm => 'Giữa khóa',
            self::Final => 'Cuối khóa',
            self::Course => 'Kết thúc khóa',
            self::Monthly => 'Hàng tháng',
            self::Quarterly => 'Hàng quý',
        };
    }
}
