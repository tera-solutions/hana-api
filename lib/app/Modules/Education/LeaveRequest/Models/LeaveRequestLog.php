<?php

namespace App\Modules\Education\LeaveRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status-transition audit row for a leave request (table `edu_leave_request_logs`).
 */
class LeaveRequestLog extends Model
{
    protected $table = 'edu_leave_request_logs';

    protected $guarded = [];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }
}
