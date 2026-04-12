<?php

namespace App\Module\Portal\Model;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_transactions';
    protected $guarded = ['id'];

    protected $fillable = [
        "view_status",
        "updated_at"
    ];
}
