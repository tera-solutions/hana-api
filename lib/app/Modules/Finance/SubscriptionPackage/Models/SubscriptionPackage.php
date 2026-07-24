<?php

namespace App\Modules\Finance\SubscriptionPackage\Models;

use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

class SubscriptionPackage extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'fin_subscription_packages';

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'sessions_included' => 'integer',
        'duration_days' => 'integer',
        'applicable_courses' => 'array',
    ];

    public const TYPE_SESSION = 'session';

    public const TYPE_MONTH = 'month';

    public const TYPE_TERM = 'term';

    public const TYPE_CUSTOM = 'custom';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function discountRules(): HasMany
    {
        return $this->hasMany(SubscriptionPackageDiscountRule::class, 'package_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'subscription_package_id');
    }
}
