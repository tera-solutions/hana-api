<?php

namespace Package\Database\Concerns;

use Package\Database\Scopes\BusinessScope;
use Package\Tenancy\TenantContext;

/**
 * Marks a model as tenant-owned: reads are confined to the active business via
 * {@see BusinessScope}, and business_id is stamped authoritatively from the
 * tenant context on create (a client-supplied value cannot forge another
 * tenant). When there is no tenant context (console, seeders, superadmin via
 * TenantContext::withoutScope) any explicit business_id is left untouched.
 *
 * Override getBusinessColumn() for models whose tenant key is not business_id.
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function ($model): void {
            $businessId = TenantContext::businessId();

            if ($businessId !== null) {
                $model->{$model->getBusinessColumn()} = $businessId;
            }
        });
    }

    public function getBusinessColumn(): string
    {
        return 'business_id';
    }
}
