<?php

namespace App\Modules\CRM\Parent\Events;

class ParentCreated
{
    public function __construct(public $model) {}
}
