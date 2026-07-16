<?php

namespace App\Modules\HR\Teacher\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

/**
 * Teacher review (table `hr_reviews`) — distinct from the Achievement module's
 * TeacherReview (`hr_teacher_reviews`).
 */
class Review extends Model
{
    use BelongsToBusiness;

    protected $table = 'hr_reviews';

    protected $guarded = [];
}
