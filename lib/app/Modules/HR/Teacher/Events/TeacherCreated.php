<?php

namespace App\Modules\HR\Teacher\Events;

class TeacherCreated
{
    public function __construct(public $model) {}
}
