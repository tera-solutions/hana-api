<?php

namespace App\Modules\Finance\Debt\Http\Resources;

use App\Modules\Finance\Debt\Services\DebtService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Presents an invoice as a debt row (debt.md §V) with computed outstanding,
 * overdue days and debt status.
 */
class DebtResource extends JsonResource
{
    public function toArray($request)
    {
        $debt = app(DebtService::class);

        return [
            'invoice_id' => $this->id,
            'invoice_no' => $this->code,
            'invoice_type' => $this->invoice_type,
            'partner_type' => $this->partner_type,
            'partner_id' => $this->partner_id,

            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,

            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'outstanding' => $this->balance_amount,
            'overdue_days' => $debt->overdueDays($this->due_date),
            'debt_status' => $debt->debtStatus($this->resource),

            'business_id' => $this->business_id,
            'business' => $this->whenLoaded('business', fn () => [
                'id' => $this->business?->id,
                'name' => $this->business?->name,
            ]),

            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),

            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'code' => $this->student->code,
                'name' => $this->student->name,
            ] : null),
        ];
    }
}
