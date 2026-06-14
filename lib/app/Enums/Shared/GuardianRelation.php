<?php

namespace App\Enums\Shared;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum GuardianRelation: string implements HasLabel
{
    use ProvidesOptions;

    case Father = 'father';
    case Mother = 'mother';
    case Guardian = 'guardian';
    case Grandfather = 'grandfather';
    case Grandmother = 'grandmother';
    case Uncle = 'uncle';
    case Aunt = 'aunt';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Father => 'Bố',
            self::Mother => 'Mẹ',
            self::Guardian => 'Người giám hộ',
            self::Grandfather => 'Ông',
            self::Grandmother => 'Bà',
            self::Uncle => 'Chú/Bác',
            self::Aunt => 'Cô/Dì',
            self::Other => 'Khác',
        };
    }
}
