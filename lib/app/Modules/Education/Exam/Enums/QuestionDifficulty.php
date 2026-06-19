<?php

namespace App\Modules\Education\Exam\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum QuestionDifficulty: string implements HasLabel
{
    use ProvidesOptions;

    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Dễ',
            self::Medium => 'Trung bình',
            self::Hard => 'Khó',
        };
    }
}
