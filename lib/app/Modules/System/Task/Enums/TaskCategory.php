<?php

namespace App\Modules\System\Task\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum TaskCategory: string implements HasLabel
{
    use ProvidesOptions;

    case General = 'general';
    case Academic = 'academic';
    case Hr = 'hr';
    case Finance = 'finance';
    case Sales = 'sales';
    case Operation = 'operation';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Chung',
            self::Academic => 'Học vụ',
            self::Hr => 'Nhân sự',
            self::Finance => 'Tài chính',
            self::Sales => 'Kinh doanh',
            self::Operation => 'Vận hành',
        };
    }
}
