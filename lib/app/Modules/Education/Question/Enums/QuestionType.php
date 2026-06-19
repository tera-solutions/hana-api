<?php

namespace App\Modules\Education\Question\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum QuestionType: string implements HasLabel
{
    use ProvidesOptions;

    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case Matching = 'matching';
    case Ordering = 'ordering';
    case FillBlank = 'fill_blank';
    case ShortAnswer = 'short_answer';
    case Essay = 'essay';
    case Speaking = 'speaking';
    case Listening = 'listening';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Một đáp án',
            self::MultipleChoice => 'Nhiều đáp án',
            self::TrueFalse => 'Đúng/Sai',
            self::Matching => 'Nối',
            self::Ordering => 'Sắp xếp',
            self::FillBlank => 'Điền khuyết',
            self::ShortAnswer => 'Trả lời ngắn',
            self::Essay => 'Tự luận',
            self::Speaking => 'Nói',
            self::Listening => 'Nghe',
        };
    }

    /**
     * Types graded against a fixed answer key — they require at least one answer (BR001).
     * Essay/Speaking are graded manually and carry no fixed answer.
     *
     * @return array<int, string>
     */
    public static function answerBacked(): array
    {
        return [
            self::SingleChoice->value,
            self::MultipleChoice->value,
            self::TrueFalse->value,
            self::Matching->value,
            self::Ordering->value,
            self::FillBlank->value,
            self::ShortAnswer->value,
            self::Listening->value,
        ];
    }

    /**
     * Types that accept exactly one correct answer (BR002).
     *
     * @return array<int, string>
     */
    public static function singleCorrect(): array
    {
        return [
            self::SingleChoice->value,
            self::TrueFalse->value,
        ];
    }
}
