<?php

namespace App\Modules\Finance\Payment\Http\Requests;

use App\Enums\Finance\PaymentMethod;
use App\Modules\Finance\Invoice\Enums\PartnerType;
use App\Modules\Finance\Payment\Enums\PaymentDirection;
use App\Modules\Finance\Payment\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a payment transaction with optional invoice allocations (payment.md §VII).
 */
class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:sys_business,id'],
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],

            'payment_direction' => ['required', 'string', Rule::in(PaymentDirection::values())],
            'payment_type' => ['nullable', 'string', Rule::in(PaymentType::values())],

            'partner_type' => ['nullable', 'string', Rule::in(PartnerType::values())],
            'partner_id' => ['nullable', 'integer'],

            'invoice_id' => ['nullable', 'integer', 'exists:fin_invoices,id'],
            'account_id' => ['nullable', 'integer', 'exists:fin_accounts,id'],

            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'method' => ['nullable', 'string', Rule::in(PaymentMethod::values())],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'pending'])],
            'description' => ['nullable', 'string', 'max:2000'],

            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer', 'exists:fin_invoices,id'],
            'allocations.*.allocated_amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_id.required' => 'Vui lòng chọn trung tâm.',
            'payment_direction.required' => 'Vui lòng chọn hướng giao dịch.',
            'payment_direction.in' => 'Hướng giao dịch không hợp lệ.',
            'amount.required' => 'Vui lòng nhập số tiền.',
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'business_id' => ['description' => 'Business id.', 'example' => 1],
            'branch_id' => ['description' => 'Branch id.', 'example' => 1],
            'payment_direction' => ['description' => 'in (thu) | out (chi).', 'example' => 'in'],
            'payment_type' => ['description' => 'tuition_payment|service_payment|debt_collection|other_income|salary_payment|teacher_payment|supplier_payment|rent_payment|marketing_payment|utility_payment|other_expense.', 'example' => 'tuition_payment'],
            'partner_type' => ['description' => 'student|parent|company|teacher|staff|supplier|landlord|partner.', 'example' => 'student'],
            'partner_id' => ['description' => 'Id of the partner within its type.', 'example' => 1],
            'invoice_id' => ['description' => 'Primary linked invoice (optional).', 'example' => 1],
            'account_id' => ['description' => 'Fund (quỹ) id whose balance moves on confirm.', 'example' => 1],
            'amount' => ['description' => 'Transaction amount.', 'example' => 1000000],
            'currency' => ['description' => 'Currency code (default VND).', 'example' => 'VND'],
            'method' => ['description' => 'cash|transfer|card|wallet|other.', 'example' => 'transfer'],
            'reference_no' => ['description' => 'Bank/e-wallet reference.', 'example' => 'FT24001'],
            'payment_date' => ['description' => 'Transaction date (default today).', 'example' => '2026-06-15'],
            'status' => ['description' => 'Initial status: draft (default) or pending.', 'example' => 'draft'],
            'description' => ['description' => 'Free-text description.', 'example' => 'Thu học phí tháng 6'],
            'allocations' => ['description' => 'Split across invoices (BR-07).', 'example' => []],
            'allocations[].invoice_id' => ['description' => 'Invoice id.', 'example' => 1],
            'allocations[].allocated_amount' => ['description' => 'Amount allocated to that invoice.', 'example' => 1000000],
        ];
    }
}
