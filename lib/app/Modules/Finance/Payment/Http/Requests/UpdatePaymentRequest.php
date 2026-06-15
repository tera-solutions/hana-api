<?php

namespace App\Modules\Finance\Payment\Http\Requests;

use App\Enums\Finance\PaymentMethod;
use App\Modules\Finance\Invoice\Enums\PartnerType;
use App\Modules\Finance\Payment\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Partial update of a draft/pending payment — number, business, direction and the
 * confirmation fields are immutable (BR-04).
 */
class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'payment_type' => ['nullable', 'string', Rule::in(PaymentType::values())],

            'partner_type' => ['nullable', 'string', Rule::in(PartnerType::values())],
            'partner_id' => ['nullable', 'integer'],

            'invoice_id' => ['nullable', 'integer', 'exists:fin_invoices,id'],
            'account_id' => ['nullable', 'integer', 'exists:fin_accounts,id'],

            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'method' => ['nullable', 'string', Rule::in(PaymentMethod::values())],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],

            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer', 'exists:fin_invoices,id'],
            'allocations.*.allocated_amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'branch_id' => ['description' => 'Branch id.', 'example' => 1],
            'payment_type' => ['description' => 'Payment type.', 'example' => 'tuition_payment'],
            'partner_type' => ['description' => 'Partner type.', 'example' => 'student'],
            'partner_id' => ['description' => 'Partner id.', 'example' => 1],
            'invoice_id' => ['description' => 'Primary linked invoice.', 'example' => 1],
            'account_id' => ['description' => 'Fund id.', 'example' => 1],
            'amount' => ['description' => 'Transaction amount.', 'example' => 1000000],
            'currency' => ['description' => 'Currency code.', 'example' => 'VND'],
            'method' => ['description' => 'cash|transfer|card|wallet|other.', 'example' => 'transfer'],
            'reference_no' => ['description' => 'Bank reference.', 'example' => 'FT24001'],
            'payment_date' => ['description' => 'Transaction date.', 'example' => '2026-06-15'],
            'description' => ['description' => 'Description.', 'example' => 'Cập nhật diễn giải'],
            'allocations' => ['description' => 'Replacement allocations (omit to keep).', 'example' => []],
            'allocations[].invoice_id' => ['description' => 'Invoice id.', 'example' => 1],
            'allocations[].allocated_amount' => ['description' => 'Allocated amount.', 'example' => 1000000],
        ];
    }
}
