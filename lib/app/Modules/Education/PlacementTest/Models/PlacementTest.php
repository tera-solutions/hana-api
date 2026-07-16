<?php

namespace App\Modules\Education\PlacementTest\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

class PlacementTest extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_placement_tests';

    protected $guarded = [];

    protected $casts = [
        'skills' => 'array',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public function results(): HasMany
    {
        return $this->hasMany(PlacementTestResult::class, 'placement_test_id');
    }
}
