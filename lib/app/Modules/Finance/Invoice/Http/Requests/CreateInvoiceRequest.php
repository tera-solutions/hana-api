<?php

namespace App\Modules\Finance\Invoice\Http\Requests;

use App\Modules\Finance\Invoice\Enums\InvoiceStatus;
use App\Modules\Finance\Invoice\Enums\InvoiceType;
use App\Modules\Finance\Invoice\Enums\PartnerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a receivable or payable invoice with its line items (invoice.md §III, §VII–VIII).
 */
class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_type' => ['required', 'string', Rule::in(InvoiceType::values())],
            'business_id' => ['required', 'integer', 'exists:sys_business,id'],
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],

            'partner_type' => ['nullable', 'string', Rule::in(PartnerType::values())],
            'partner_id' => ['nullable', 'integer'],

            'student_id' => ['nullable', 'integer', 'exists:edu_students,id'],
            'parent_id' => ['nullable', 'integer', 'exists:crm_parents,id'],
            'enrollment_id' => ['nullable', 'integer', 'exists:edu_enrollments,id'],

            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],

            'status' => ['nullable', 'string', Rule::in(InvoiceStatus::values())],

            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],

            'note' => ['nullable', 'string', 'max:2000'],

            'items' => ['nullable', 'array'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_type.required' => 'Vui lòng chọn loại hóa đơn.',
            'invoice_type.in' => 'Loại hóa đơn không hợp lệ.',
            'business_id.required' => 'Vui lòng chọn trung tâm.',
            'items.*.name.required' => 'Tên khoản mục không được để trống.',
            'items.*.unit_price.required' => 'Đơn giá không được để trống.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'invoice_type' => ['description' => 'receivable|payable.', 'example' => 'receivable'],
            'business_id' => ['description' => 'Business (trung tâm) id.', 'example' => 1],
            'branch_id' => ['description' => 'Branch id.', 'example' => 1],
            'partner_type' => ['description' => 'student|parent|company|teacher|staff|supplier|landlord|partner.', 'example' => 'student'],
            'partner_id' => ['description' => 'Id of the partner within its type.', 'example' => 1],
            'student_id' => ['description' => 'Student id (receivable).', 'example' => 1],
            'parent_id' => ['description' => 'Parent id (receivable).', 'example' => 1],
            'enrollment_id' => ['description' => 'Source enrollment id.', 'example' => 1],
            'invoice_date' => ['description' => 'Issue date (defaults to today).', 'example' => '2026-06-15'],
            'due_date' => ['description' => 'Payment due date.', 'example' => '2026-06-30'],
            'status' => ['description' => 'Initial status (defaults to pending for receivable, draft for payable).', 'example' => 'pending'],
            'subtotal' => ['description' => 'Amount before discount/tax (ignored when items are sent).', 'example' => 1000000],
            'discount' => ['description' => 'Discount amount.', 'example' => 0],
            'tax' => ['description' => 'Tax amount.', 'example' => 0],
            'note' => ['description' => 'Free-text note.', 'example' => 'Học phí tháng 6'],
            'items' => ['description' => 'Invoice line items.', 'example' => []],
            'items[].name' => ['description' => 'Line item name.', 'example' => 'Học phí khóa IELTS'],
            'items[].quantity' => ['description' => 'Quantity (default 1).', 'example' => 1],
            'items[].unit_price' => ['description' => 'Unit price.', 'example' => 1000000],
            'items[].total' => ['description' => 'Line total (defaults to quantity * unit_price).', 'example' => 1000000],
        ];
    }
}
