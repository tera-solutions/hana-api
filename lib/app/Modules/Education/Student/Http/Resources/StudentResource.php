<?php

namespace App\Modules\Education\Student\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}