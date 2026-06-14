<?php

namespace App\Enums\Concerns;

use App\Enums\Shared\BadgeColor;

/**
 * Implements HasBadge for a status enum by mapping each case to a semantic
 * BadgeColor in badge(); the concrete hex values stay in BadgeColor.
 */
trait ResolvesBadge
{
    abstract public function badge(): BadgeColor;

    public function color(): string
    {
        return $this->badge()->color();
    }

    public function backgroundColor(): string
    {
        return $this->badge()->backgroundColor();
    }
}
