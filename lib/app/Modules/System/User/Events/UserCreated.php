<?php

namespace App\Modules\System\User\Events;

class UserCreated
{
    public function __construct(public $model) {}
}
