<?php

namespace App\Module\Portal\Model;

use App\Module\Portal\Model\Epic;
use App\Module\Portal\Model\Module;
use Illuminate\Database\Eloquent\Model;

class GroupPageControl extends Model
{
    protected $connection = 'admin';
    protected $table = 'ad_group_page_controls';
    protected $fillable = [
        'title',
        'code',
        'concatenated_code',
        'epic_id',
        'module_id',
        'created_by',
        'updated_by',
    ];

    public function epic()
    {
        return $this->beLongsTo(Epic::class, 'epic_id');
    }

    public function module()
    {
        return $this->beLongsTo(Module::class, 'module_id');
    }
}
