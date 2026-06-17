<?php

namespace App\Modules\Education\Room\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum RoomType: string implements HasLabel
{
    use ProvidesOptions;

    case Classroom = 'classroom';
    case ComputerRoom = 'computer_room';
    case SpeakingRoom = 'speaking_room';
    case ExamRoom = 'exam_room';
    case MeetingRoom = 'meeting_room';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Classroom => 'Phòng học',
            self::ComputerRoom => 'Phòng máy tính',
            self::SpeakingRoom => 'Phòng luyện nói',
            self::ExamRoom => 'Phòng thi',
            self::MeetingRoom => 'Phòng họp',
            self::Other => 'Khác',
        };
    }
}
