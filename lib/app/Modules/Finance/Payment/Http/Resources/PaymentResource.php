<?php

namespace App\Modules\Finance\Payment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'payment_no' => $this->payment_no,
            'payment_date' => $this->payment_date,
            'payment_direction' => $this->payment_direction,
            'payment_type' => $this->payment_type,
            'status' => $this->status,

            'partner_type' => $this->partner_type,
            'partner_id' => $this->partner_id,

            'amount' => $this->amount,
            'currency' => $this->currency,
            'method' => $this->method,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
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

            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => $this->account ? [
                'id' => $this->account->id,
                'code' => $this->account->code,
                'name' => $this->account->name,
                'type' => $this->account->type,
                'balance' => $this->account->balance,
            ] : null),

            'invoice_id' => $this->invoice_id,
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice ? [
                'id' => $this->invoice->id,
                'code' => $this->invoice->code,
                'total' => $this->invoice->total,
                'balance_amount' => $this->invoice->balance_amount,
                'status' => $this->invoice->status,
            ] : null),

            'parent_payment_id' => $this->parent_payment_id,

            'allocations' => $this->whenLoaded('allocations', fn () => $this->allocations->map(fn ($allocation) => [
                'id' => $allocation->id,
                'invoice_id' => $allocation->invoice_id,
                'allocated_amount' => $allocation->allocated_amount,
            ])),

            'confirmed_by' => $this->confirmed_by,
            'confirmed_at' => $this->confirmed_at,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
