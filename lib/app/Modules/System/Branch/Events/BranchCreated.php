<?php

namespace App\Modules\System\Branch\Events;

class BranchCreated
{
    public function __construct(public $model) {}
}
