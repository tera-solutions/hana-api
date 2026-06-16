<?php

namespace App\Modules\Finance\Invoice\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'invoice_type' => $this->invoice_type,
            'status' => $this->status,

            'partner_type' => $this->partner_type,
            'partner_id' => $this->partner_id,

            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'paid_at' => $this->paid_at,

            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'balance_amount' => $this->balance_amount,

            'note' => $this->note,

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

            'enrollment_id' => $this->enrollment_id,

            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ])),

            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_direction' => $payment->payment_direction,
                'method' => $payment->method,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'paid_at' => $payment->paid_at,
            ])),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
