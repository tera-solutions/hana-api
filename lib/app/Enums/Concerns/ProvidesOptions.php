<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

/**
 * Helpers for turning a labelled backed enum into the `{ key, value, label }` option
 * lists the frontend consumes, plus the bare value list for validation rules.
 *
 * @mixin \BackedEnum
 * @mixin HasLabel
 */
trait ProvidesOptions
{
    /**
     * Status enums (those implementing HasBadge) additionally carry `color` and
     * `backgroundColor` hex values for rendering badges.
     *
     * @return array<int, array{key: string, value: int|string, label: string, color?: string, backgroundColor?: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'key' => Str::upper(Str::snake($case->name)),
                'value' => $case->value,
                'label' => $case->label(),
                ...($case instanceof HasBadge
                    ? ['color' => $case->color(), 'backgroundColor' => $case->backgroundColor()]
                    : []),
            ],
            self::cases(),
        );
    }

    /**
     * Backing values, e.g. for `Rule::in(Status::values())`.
     *
     * @return array<int, int|string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
