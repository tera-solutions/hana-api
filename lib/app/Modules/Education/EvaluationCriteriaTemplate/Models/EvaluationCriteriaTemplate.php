<?php

namespace App\Modules\Education\EvaluationCriteriaTemplate\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

/**
 * A reusable rubric (table `edu_evaluation_criteria_templates`) — a named
 * list of criteria to pre-fill when creating an Evaluation. `is_shared`
 * templates (admin-only to author) are visible to every teacher in the
 * business; non-shared ones are private to their creator.
 */
class EvaluationCriteriaTemplate extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_evaluation_criteria_templates';

    protected $guarded = [];

    protected $casts = [
        'criteria' => 'array',
        'criteria_descriptions' => 'array',
        'is_shared' => 'boolean',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
