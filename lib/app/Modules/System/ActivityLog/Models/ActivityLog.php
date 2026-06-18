<?php

namespace App\Modules\System\ActivityLog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit log (spec 028). Immutable by business rule: created only,
 * never updated (BR-02) or deleted manually (BR-03/BR-06).
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'sys_activity_logs';

    protected $guarded = [];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'changed_fields' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // BR-02 / BR-03: logs cannot be mutated or deleted once written.
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
