<?php

namespace App\Modules\Education\Course\Events;

class CourseCreated
{
    public function __construct(public $model){}
}