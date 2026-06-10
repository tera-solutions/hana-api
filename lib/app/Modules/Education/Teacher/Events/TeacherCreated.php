<?php

namespace App\Modules\Education\Teacher\Events;

class TeacherCreated
{
    public function __construct(public $model) {}
}
