<?php

namespace Package\Database\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Auto-stamps "who" audit columns, the actor counterpart to Laravel's
 * timestamps / SoftDeletes:
 *
 *   - created_by  — set once, when the row is created
 *   - updated_by  — set on create and on every update
 *   - deleted_by  — set when the row is soft-deleted, cleared on restore
 *
 * Usage mirrors SoftDeletes:
 *   - Migration:  $table->auditColumns();   // adds the three columns
 *   - Model:      use HasAuditFields;
 *
 * The acting user is resolved from the `api` guard first (this is an API-only
 * app), falling back to the default guard. Override auditUserId() to change it,
 * or getAuditColumns() to track only a subset.
 */
trait HasAuditFields
{
    public static function bootHasAuditFields(): void
    {
        static::creating(function (Model $model) {
            $model->stampAuditColumn('created_by');
            $model->stampAuditColumn('updated_by', force: true);
        });

        static::updating(function (Model $model) {
            $model->stampAuditColumn('updated_by', force: true);
        });

        static::deleting(function (Model $model) {
            // Only stamp deleted_by for soft deletes (skip hard/force deletes).
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            if ($model->stampAuditColumn('deleted_by', force: true)) {
                $model->saveQuietly();
            }
        });

        // restoring() only exists on SoftDeletes models.
        if (method_exists(static::class, 'restoring')) {
            static::restoring(function (Model $model) {
                $model->setAttribute('deleted_by', null);
            });
        }
    }

    /**
     * Columns this model tracks. Override to track a subset.
     *
     * @return string[]
     */
    public function getAuditColumns(): array
    {
        return ['created_by', 'updated_by', 'deleted_by'];
    }

    /**
     * Resolve the acting user id. Override per model if needed.
     */
    protected function auditUserId(): int|string|null
    {
        return Auth::guard('api')->id() ?? Auth::id();
    }

    /**
     * Stamp a column with the current actor. When $force is false the value is
     * only set if currently empty (used for created_by). Does nothing when there
     * is no authenticated actor, so system/background saves (e.g. updating the
     * login timestamp) never wipe an existing value. Returns true if a value was
     * actually written.
     */
    protected function stampAuditColumn(string $column, bool $force = false): bool
    {
        if (! in_array($column, $this->getAuditColumns(), true)) {
            return false;
        }

        $userId = $this->auditUserId();

        if ($userId === null) {
            return false;
        }

        // Read the raw attribute (not $this->{$column}) so a model that also
        // defines a relationship method named like an audit column (e.g. a
        // created_by() belongsTo) doesn't trigger relation resolution here.
        $current = $this->getAttributes()[$column] ?? null;

        if (! $force && ! empty($current)) {
            return false;
        }

        $this->setAttribute($column, $userId);

        return true;
    }
}
