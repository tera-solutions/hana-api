<?php

namespace App\Modules\Education\Student\Events;

class StudentCreated
{
    public function __construct(public $model){}
}