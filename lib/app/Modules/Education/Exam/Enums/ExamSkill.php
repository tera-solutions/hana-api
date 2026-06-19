<?php

namespace App\Modules\Education\Exam\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ExamSkill: string implements HasLabel
{
    use ProvidesOptions;

    case Listening = 'listening';
    case Speaking = 'speaking';
    case Reading = 'reading';
    case Writing = 'writing';
    case Grammar = 'grammar';
    case Vocabulary = 'vocabulary';

    public function label(): string
    {
        return match ($this) {
            self::Listening => 'Nghe',
            self::Speaking => 'Nói',
            self::Reading => 'Đọc',
            self::Writing => 'Viết',
            self::Grammar => 'Ngữ pháp',
            self::Vocabulary => 'Từ vựng',
        };
    }
}
