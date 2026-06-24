<?php

namespace App\Modules\System\Task\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum TaskRelatedType: string implements HasLabel
{
    use ProvidesOptions;

    case Student = 'student';
    case Parent = 'parent';
    case Lead = 'lead';
    case Course = 'course';
    case ClassRoom = 'class';
    case Teacher = 'teacher';
    case Invoice = 'invoice';
    case Payment = 'payment';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Học viên',
            self::Parent => 'Phụ huynh',
            self::Lead => 'Tiềm năng',
            self::Course => 'Khóa học',
            self::ClassRoom => 'Lớp học',
            self::Teacher => 'Giáo viên',
            self::Invoice => 'Hóa đơn',
            self::Payment => 'Thanh toán',
        };
    }
}
