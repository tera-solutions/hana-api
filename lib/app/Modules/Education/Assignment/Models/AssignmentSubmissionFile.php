<?php

namespace App\Modules\Education\Assignment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmissionFile extends Model
{
    protected $table = 'edu_assignment_submission_files';

    protected $guarded = [];

    protected $casts = [
        'file_id' => 'integer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }
}
