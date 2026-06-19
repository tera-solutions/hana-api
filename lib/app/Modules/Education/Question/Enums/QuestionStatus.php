<?php

namespace App\Modules\Education\Question\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum QuestionStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Draft = 'draft';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Reviewing => 'Đang duyệt',
            self::Approved => 'Đã duyệt',
            self::Active => 'Đang sử dụng',
            self::Archived => 'Lưu trữ',
        };
    }
}
