<?php

namespace App\Module\Portal\Model;

use Illuminate\Database\Eloquent\Model;

class Epic extends Model
{
    protected $connection = 'admin';
    protected $table = 'ad_epics';
    protected $guarded = ['id'];
    protected $fillable = [
        'name',
        'code',
        'concatenated_code',
        'module_id',
        'icon',
        'status',
        'created_by',
        'updated_by'
    ];

    public function controls()
    {
        return $this->hasMany(GroupPageControl::class, 'epic_id');
    }

    public function pages()
    {
        return $this->hasMany(PageView::class, 'epic_id');
    }
}
