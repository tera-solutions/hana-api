<?php

namespace App\Enums\Concerns;

/**
 * A backed enum that exposes a human-readable (Vietnamese) label for each case,
 * so it can be rendered in the UI and shipped through the metadata endpoint.
 */
interface HasLabel
{
    public function label(): string;
}
