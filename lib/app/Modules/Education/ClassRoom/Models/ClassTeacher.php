<?php

namespace App\Modules\Education\ClassRoom\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

/**
 * Co-teacher / assistant assignment pivot (table `edu_class_teacher`).
 */
class ClassTeacher extends Model
{
    use BelongsToBusiness;

    protected $table = 'edu_class_teacher';

    protected $guarded = [];
}
