<?php

namespace App\Modules\HR\Payroll\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'month' => $this->month,
            'year' => $this->year,
            'total_hours' => $this->total_hours,
            'base_salary' => $this->base_salary,
            'bonus' => $this->bonus,
            'penalty' => $this->penalty,
            'total_salary' => $this->total_salary,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
