<?php

namespace App\Modules\Education\Assignment\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum AssignmentStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Soạn thảo',
            self::Published => 'Đã giao',
            self::Closed => 'Hết hạn',
        };
    }
}
