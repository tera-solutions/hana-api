<?php

namespace App\Modules\Education\Exam\Models;

use App\Models\Media;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class ExamQuestion extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_exam_questions';

    protected $guarded = [];

    protected $casts = [
        'answer_key' => 'array',
        'score' => 'decimal:2',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'file_id');
    }
}
