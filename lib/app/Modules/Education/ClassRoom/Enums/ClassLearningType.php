<?php

namespace App\Modules\Education\ClassRoom\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ClassLearningType: string implements HasLabel
{
    use ProvidesOptions;

    case Scheduled = 'scheduled';
    case SelfLearning = 'self_learning';
    case Flexible = 'flexible';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Theo lịch cố định',
            self::SelfLearning => 'Tự học',
            self::Flexible => 'Linh hoạt',
        };
    }
}
