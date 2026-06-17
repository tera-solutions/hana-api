<?php

namespace App\Modules\Education\LessonPlanMaterial\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum MaterialType: string implements HasLabel
{
    use ProvidesOptions;

    case Pdf = 'pdf';
    case Video = 'video';
    case Audio = 'audio';
    case Slide = 'slide';
    case Worksheet = 'worksheet';
    case Homework = 'homework';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Video => 'Video',
            self::Audio => 'Audio',
            self::Slide => 'Slide',
            self::Worksheet => 'Worksheet',
            self::Homework => 'Bài tập',
        };
    }
}
