<?php

namespace Package\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Package\Tenancy\TenantContext;

/**
 * Global scope that confines every query on a tenant-owned model to the
 * active business. A null tenant context (unauthenticated / console /
 * superadmin) leaves the query unscoped.
 */
class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $businessId = TenantContext::businessId();

        if ($businessId === null) {
            return;
        }

        $column = method_exists($model, 'getBusinessColumn')
            ? $model->getBusinessColumn()
            : 'business_id';

        $builder->where($model->qualifyColumn($column), $businessId);
    }
}
