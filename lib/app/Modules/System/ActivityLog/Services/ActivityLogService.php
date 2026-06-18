<?php

namespace App\Modules\System\ActivityLog\Services;

use App\Modules\System\ActivityLog\Models\ActivityLog;
use Illuminate\Support\Carbon;
use Package\Database\Concerns\HandlesEntityQueries;

class ActivityLogService
{
    use HandlesEntityQueries;

    /** Sensitive keys masked before persisting (spec 028 BR-04). */
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'old_password', 'new_password',
        'refresh_token', 'access_token', 'token', 'otp', 'secret', 'secure_code',
        'credit_card', 'card_number', 'cvv', 'pin',
    ];

    /**
     * Persist a single audit entry. The only write path — masks sensitive data
     * and stamps created_at. Logs are immutable afterwards (BR-02).
     */
    public function write(array $attributes): ActivityLog
    {
        foreach (['old_data', 'new_data', 'changed_fields'] as $key) {
            if (! empty($attributes[$key]) && is_array($attributes[$key])) {
                $attributes[$key] = $this->mask($attributes[$key]);
            }
        }

        $attributes['created_at'] = $attributes['created_at'] ?? now();

        return ActivityLog::create($attributes);
    }

    /**
     * Paginated, filterable list (spec 028 §IV).
     */
    public function paginate(array $params = [])
    {
        $query = ActivityLog::query()->with('user');

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('entity_id', 'like', "%{$search}%");
            });
        }

        foreach (['module', 'entity', 'action', 'status', 'user_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applyDateRange($query, $params);

        $this->applySort($query, $params, ['created_at', 'module', 'action', 'user_id'], 'created_at');

        return $query->paginate($this->resolvePerPage($params));
    }

    public function find($id): ActivityLog
    {
        return ActivityLog::with('user')->findOrFail($id);
    }

    /**
     * Aggregate counters (spec 028 §XII).
     */
    public function statistics(array $params = []): array
    {
        $base = fn () => $this->applyDateRange(ActivityLog::query(), $params);

        return [
            'total' => $base()->count(),
            'by_module' => $base()->selectRaw('module, count(*) as total')
                ->groupBy('module')->pluck('total', 'module'),
            'by_action' => $base()->selectRaw('action, count(*) as total')
                ->groupBy('action')->pluck('total', 'action'),
            'top_users' => $base()->whereNotNull('user_id')
                ->selectRaw('user_id, count(*) as total')
                ->groupBy('user_id')->orderByDesc('total')->limit(10)
                ->pluck('total', 'user_id'),
            'failed_logins' => $base()->where('action', 'login')->where('status', 'failed')->count(),
        ];
    }

    /**
     * Export stub — returns downloadable file metadata (generation deferred).
     */
    public function export(array $params = []): array
    {
        $now = now()->getTimestamp();

        return [
            'file_name' => "export_activity_log_{$now}.xlsx",
            'created_at' => now(),
            'link' => asset("/assets/export/activity-log/export_activity_log_{$now}.xlsx"),
        ];
    }

    /**
     * Apply the spec §IV time filters: explicit range or a relative period.
     */
    private function applyDateRange($query, array $params)
    {
        if (! empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        $since = match ($params['period'] ?? null) {
            'today' => Carbon::today(),
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            default => null,
        };

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query;
    }

    /**
     * Recursively replace sensitive values with a mask.
     */
    private function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->mask($value);
            } elseif (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
