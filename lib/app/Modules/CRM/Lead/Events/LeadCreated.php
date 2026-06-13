<?php

namespace App\Modules\CRM\Lead\Events;

class LeadCreated
{
    public function __construct(public $model) {}
}
