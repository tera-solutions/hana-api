<?php

namespace Package\Database\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Shared query helpers for CRUD services: linked-data counting (for delete
 * guards / statistics), sort whitelisting and page-size clamping.
 */
trait HandlesEntityQueries
{
    /**
     * Count rows in $table whose $column references $id. A missing table or
     * column is treated as "no linked data" (returns 0).
     */
    protected function countLinked(string $table, $id, string $column): int
    {
        try {
            return (int) DB::table($table)->where($column, $id)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Whether any of the given [table => column] pairs reference $id.
     *
     * @param  array<string, string>  $linkedTables
     */
    protected function hasLinkedData($id, array $linkedTables): bool
    {
        foreach ($linkedTables as $table => $column) {
            if ($this->countLinked($table, $id, $column) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply an allow-listed sort to the query.
     *
     * @param  string[]  $sortable
     */
    protected function applySort(Builder $query, array $params, array $sortable, string $default = 'created_at'): void
    {
        $sortBy = in_array($params['sort_by'] ?? '', $sortable, true) ? $params['sort_by'] : $default;
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
    }

    /**
     * Resolve the page size from $params, clamped to the allowed options.
     *
     * @param  int[]  $allowed
     */
    protected function resolvePerPage(array $params, array $allowed = [20, 50, 100], int $default = 20): int
    {
        $perPage = (int) ($params['per_page'] ?? $default);

        return in_array($perPage, $allowed, true) ? $perPage : $default;
    }
}
