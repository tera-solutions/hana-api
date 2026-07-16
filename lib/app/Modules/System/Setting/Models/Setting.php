<?php

namespace App\Modules\System\Setting\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class Setting extends Model
{
    use BelongsToBusiness;

    protected $table = 'sys_settings';

    protected $guarded = [];
}
