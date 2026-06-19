<?php

namespace App\Modules\Education\Exam\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ExamStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Published => 'Đã phát hành',
            self::Archived => 'Lưu trữ',
        };
    }
}
