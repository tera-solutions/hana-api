<?php

namespace App\Modules\Finance\InvoiceConfig\Http\Requests;

use App\Modules\Finance\InvoiceConfig\Models\InvoiceConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auto_generate' => ['required', 'boolean'],
            'billing_day' => ['required', 'integer', 'min:1', 'max:28'],
            'due_days' => ['required', 'integer', 'min:0', 'max:60'],
            'late_fee_enabled' => ['nullable', 'boolean'],
            'late_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100', Rule::requiredIf(fn () => (bool) $this->input('late_fee_enabled'))],
            'unpaid_student_status' => ['nullable', Rule::in([
                InvoiceConfig::STUDENT_STATUS_DEBT,
                InvoiceConfig::STUDENT_STATUS_SUSPENDED,
            ])],
            'reminder' => ['nullable', 'array'],
            'reminder.before_due_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'reminder.on_overdue' => ['nullable', 'boolean'],
            'reminder.channels' => ['nullable', 'array'],
            'reminder.channels.*' => [Rule::in(['app', 'sms', 'email'])],
        ];
    }

    public function messages(): array
    {
        return [
            'billing_day.max' => 'Ngày lập hóa đơn phải từ 1 đến 28 (để hợp lệ với mọi tháng).',
            'due_days.min' => 'Số ngày thanh toán phải lớn hơn hoặc bằng 0.',
            'late_fee_percent.required' => 'Vui lòng nhập phần trăm phí trễ hạn.',
            'late_fee_percent.max' => 'Phần trăm phí trễ hạn không hợp lệ.',
            'unpaid_student_status.in' => 'Trạng thái học viên không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'auto_generate' => ['description' => 'Bật/tắt tự động tạo hóa đơn hàng tháng.', 'example' => true],
            'billing_day' => ['description' => 'Ngày trong tháng để lập hóa đơn (1-28).', 'example' => 1],
            'due_days' => ['description' => 'Số ngày cho phép thanh toán kể từ ngày lập.', 'example' => 7],
            'late_fee_enabled' => ['description' => 'Bật phí trễ hạn.', 'example' => true],
            'late_fee_percent' => ['description' => '% phí trễ hạn mỗi ngày trễ (bắt buộc khi bật).', 'example' => 2],
            'unpaid_student_status' => ['description' => 'Trạng thái học viên khi có hóa đơn quá hạn chưa thanh toán: debt|suspended (mặc định debt).', 'example' => 'suspended'],
            'reminder' => ['description' => 'Cấu hình nhắc thanh toán.'],
            'reminder.before_due_days' => ['description' => 'Gửi nhắc trước hạn (số ngày).', 'example' => 3],
            'reminder.on_overdue' => ['description' => 'Gửi nhắc khi quá hạn.', 'example' => true],
            'reminder.channels' => ['description' => 'Kênh gửi: app|sms|email.', 'example' => ['app', 'sms']],
        ];
    }
}
