<?php

namespace App\Modules\System\Business\Events;

class BusinessCreated
{
    public function __construct(public $model) {}
}
