<?php

namespace App\Enums\Concerns;

/**
 * Helpers for turning a labelled backed enum into the `{ value, label }` option
 * lists the frontend consumes, plus the bare value list for validation rules.
 *
 * @mixin \BackedEnum
 * @mixin HasLabel
 */
trait ProvidesOptions
{
    /**
     * @return array<int, array{value: int|string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
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
