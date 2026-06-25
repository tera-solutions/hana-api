<?php

namespace App\Modules\Education\Evaluation\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum EvaluatorType: string implements HasLabel
{
    use ProvidesOptions;

    case Parent = 'parent';
    case Student = 'student';
    case Manager = 'manager';
    case Teacher = 'teacher';
    case Cskh = 'cskh';

    public function label(): string
    {
        return match ($this) {
            self::Parent => 'Phụ huynh',
            self::Student => 'Học viên',
            self::Manager => 'Quản lý',
            self::Teacher => 'Giáo viên',
            self::Cskh => 'Chăm sóc khách hàng',
        };
    }
}
