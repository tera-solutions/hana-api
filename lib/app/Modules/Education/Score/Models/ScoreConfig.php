<?php

namespace App\Modules\Education\Score\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

class ScoreConfig extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;

    protected $table = 'edu_score_configs';

    protected $guarded = [];

    protected $casts = [
        'components' => 'array',
    ];

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }
}
