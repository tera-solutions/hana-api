<?php

namespace App\Modules\Education\Material\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum MaterialType: string implements HasLabel
{
    use ProvidesOptions;

    case Pdf = 'pdf';
    case Document = 'document';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Presentation = 'presentation';
    case Worksheet = 'worksheet';
    case Homework = 'homework';
    case Exam = 'exam';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Document => 'Document',
            self::Image => 'Image',
            self::Video => 'Video',
            self::Audio => 'Audio',
            self::Presentation => 'Presentation',
            self::Worksheet => 'Worksheet',
            self::Homework => 'Bài tập',
            self::Exam => 'Bài kiểm tra',
            self::Other => 'Khác',
        };
    }
}
