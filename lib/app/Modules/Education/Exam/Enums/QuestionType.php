<?php

namespace App\Modules\Education\Exam\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum QuestionType: string implements HasLabel
{
    use ProvidesOptions;

    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case FillBlank = 'fill_blank';
    case Matching = 'matching';
    case Essay = 'essay';
    case Speaking = 'speaking';
    case Listening = 'listening';
    case PaperUpload = 'paper_upload';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Một đáp án',
            self::MultipleChoice => 'Nhiều đáp án',
            self::FillBlank => 'Điền khuyết',
            self::Matching => 'Nối',
            self::Essay => 'Tự luận',
            self::Speaking => 'Nói',
            self::Listening => 'Nghe',
            self::PaperUpload => 'Đề giấy / PDF',
        };
    }

    /**
     * Types that can be graded automatically (exam.md §XI "Auto Grade").
     *
     * @return array<int, string>
     */
    public static function autoGradable(): array
    {
        return [
            self::SingleChoice->value,
            self::MultipleChoice->value,
            self::FillBlank->value,
            self::Matching->value,
        ];
    }
}
