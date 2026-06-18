<?php

namespace App\Modules\System\ActivityLog\Concerns;

use App\Modules\System\ActivityLog\Support\ActivityLogger;

/**
 * Emits an audit entry (spec 028) whenever the model is created, updated, soft-
 * deleted or restored. The actor/request context and sensitive-field masking are
 * handled downstream by ActivityLogger / ActivityLogService.
 *
 * A model may narrow what is captured with `protected array $activityExclude = [...]`.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->recordActivity('created'));
        static::updated(fn ($model) => $model->recordActivity('updated'));
        static::deleted(fn ($model) => $model->recordActivity('deleted'));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn ($model) => $model->recordActivity('restored'));
        }
    }

    protected function recordActivity(string $action): void
    {
        [$old, $new] = $this->activityChanges($action);

        // An "update" that only touched timestamps/audit columns is not worth logging.
        if ($action === 'updated' && $old === null && $new === null) {
            return;
        }

        $entity = class_basename($this);

        ActivityLogger::log([
            'module' => $this->activityModule(),
            'entity' => $entity,
            'entity_id' => $this->getKey(),
            'action' => $action,
            'old_data' => $old,
            'new_data' => $new,
            'changed_fields' => array_keys($new ?? $old ?? []) ?: null,
            'description' => ucfirst($action).' '.$entity.' #'.$this->getKey(),
        ]);
    }

    /**
     * @return array{0: array<string,mixed>|null, 1: array<string,mixed>|null} [old, new]
     */
    protected function activityChanges(string $action): array
    {
        $excluded = array_flip($this->activityExcluded());

        if ($action === 'updated') {
            $new = array_diff_key($this->getChanges(), $excluded);

            if (empty($new)) {
                return [null, null];
            }

            return [array_intersect_key($this->getOriginal(), $new), $new];
        }

        $attributes = array_diff_key($this->getAttributes(), $excluded);

        // A delete captures the prior state as old_data; create/restore as new_data.
        return $action === 'deleted' ? [$attributes, null] : [null, $attributes];
    }

    /**
     * Columns never worth auditing: timestamps, audit stamps and any model opt-outs.
     *
     * @return list<string>
     */
    protected function activityExcluded(): array
    {
        return array_merge(
            ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by', 'remember_token'],
            property_exists($this, 'activityExclude') ? $this->activityExclude : [],
        );
    }

    /**
     * The owning domain, derived from the module namespace (e.g. Education → "education").
     */
    protected function activityModule(): string
    {
        $parts = explode('\\', static::class);
        $index = array_search('Modules', $parts, true);

        return strtolower($index !== false ? ($parts[$index + 1] ?? 'app') : 'app');
    }
}
