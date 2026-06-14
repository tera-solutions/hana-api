<?php

namespace App\Enums\Shared;

/**
 * Semantic badge palette — the single source of truth for the hex colors the
 * frontend uses to render status badges. Status enums map each case to one of
 * these tokens via the ResolvesBadge trait, so colors live in exactly one place.
 */
enum BadgeColor
{
    case Success;
    case Warning;
    case Danger;
    case Info;
    case Neutral;

    /** Text color (hex). */
    public function color(): string
    {
        return match ($this) {
            self::Success => '#166534',
            self::Warning => '#92400e',
            self::Danger => '#991b1b',
            self::Info => '#1e40af',
            self::Neutral => '#374151',
        };
    }

    /** Badge background color (hex). */
    public function backgroundColor(): string
    {
        return match ($this) {
            self::Success => '#dcfce7',
            self::Warning => '#fef3c7',
            self::Danger => '#fee2e2',
            self::Info => '#dbeafe',
            self::Neutral => '#f3f4f6',
        };
    }
}
