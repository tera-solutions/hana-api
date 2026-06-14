<?php

namespace App\Enums\Concerns;

/**
 * A status enum that renders as a colored badge. Implemented via ResolvesBadge,
 * which derives both colors from a semantic BadgeColor token. When a case
 * implements this, ProvidesOptions::options() emits `color` + `backgroundColor`.
 */
interface HasBadge
{
    /** Text color (hex). */
    public function color(): string;

    /** Badge background color (hex). */
    public function backgroundColor(): string;
}
