<?php

namespace App\Modules\System\Subscription\Models;

use App\Modules\System\Package\Models\Package;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;

class Subscription extends Model
{
    use BelongsToBusiness;

    protected $table = 'sys_subscriptions';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'date',
        'expires_at' => 'date',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
