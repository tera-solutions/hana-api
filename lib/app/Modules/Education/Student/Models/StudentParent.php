<?php

namespace App\Modules\Education\Student\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Thin model over the shared `crm_parents` table, used for assigning parents /
 * guardians to a student. The full Parent (CRM) feature lives in its own module;
 * this only covers what Student create/update needs.
 */
class StudentParent extends Model
{
    protected $table = 'crm_parents';

    protected $guarded = [];
}
