<?php

namespace Package\Tenancy;

use Illuminate\Support\Facades\Auth;

/**
 * Resolves the business (tenant) whose data the current request may touch.
 *
 * Default source is the authenticated API user's business_id. A request with
 * no authenticated user (login, console, seeders) is unscoped — callers that
 * need cross-tenant access (platform superadmin) use withoutScope(); callers
 * acting into a specific tenant use actingAs().
 */
class TenantContext
{
    private static ?int $override = null;

    private static bool $hasOverride = false;

    private static bool $disabled = false;

    /**
     * The active tenant's business id, or null when the query must not be
     * business-scoped (unauthenticated, console, or explicitly disabled).
     */
    public static function businessId(): ?int
    {
        if (self::$disabled) {
            return null;
        }

        if (self::$hasOverride) {
            return self::$override;
        }

        $user = Auth::guard('api')->user() ?? Auth::user();

        return $user && $user->business_id ? (int) $user->business_id : null;
    }

    /**
     * Force scoping to a specific business for the duration of $callback.
     */
    public static function actingAs(?int $businessId, callable $callback): mixed
    {
        $previousOverride = self::$override;
        $previousHasOverride = self::$hasOverride;
        $previousDisabled = self::$disabled;

        self::$override = $businessId;
        self::$hasOverride = true;
        self::$disabled = false;

        try {
            return $callback();
        } finally {
            self::$override = $previousOverride;
            self::$hasOverride = $previousHasOverride;
            self::$disabled = $previousDisabled;
        }
    }

    /**
     * Disable business scoping for the duration of $callback (superadmin,
     * cross-tenant maintenance). Auto-fill on create is also skipped.
     */
    public static function withoutScope(callable $callback): mixed
    {
        $previousDisabled = self::$disabled;
        self::$disabled = true;

        try {
            return $callback();
        } finally {
            self::$disabled = $previousDisabled;
        }
    }

    /** Reset all overrides. Intended for test teardown. */
    public static function flush(): void
    {
        self::$override = null;
        self::$hasOverride = false;
        self::$disabled = false;
    }
}
