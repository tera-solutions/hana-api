<?php

namespace App\Modules\Education\Assignment\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum AssignmentType: string implements HasLabel
{
    use ProvidesOptions;

    case Homework = 'homework';
    case Worksheet = 'worksheet';
    case Quiz = 'quiz';
    case Writing = 'writing';
    case Speaking = 'speaking';
    case Listening = 'listening';
    case Reading = 'reading';
    case Project = 'project';
    case ExamPractice = 'exam_practice';

    public function label(): string
    {
        return match ($this) {
            self::Homework => 'Bài tập về nhà',
            self::Worksheet => 'Phiếu bài tập',
            self::Quiz => 'Trắc nghiệm',
            self::Writing => 'Viết',
            self::Speaking => 'Nói',
            self::Listening => 'Nghe',
            self::Reading => 'Đọc hiểu',
            self::Project => 'Dự án',
            self::ExamPractice => 'Luyện thi',
        };
    }
}
