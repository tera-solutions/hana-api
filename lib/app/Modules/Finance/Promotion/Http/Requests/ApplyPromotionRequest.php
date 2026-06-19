<?php

namespace App\Modules\Finance\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'promotion_id' => ['nullable', 'integer', 'exists:fin_promotions,id'],
            'voucher_code' => ['nullable', 'string', 'required_without:promotion_id'],
            'enrollment_id' => ['nullable', 'integer'],
            'invoice_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Giá trị đơn hàng là bắt buộc.',
            'amount.gt' => 'Giá trị đơn hàng phải lớn hơn 0.',
            'voucher_code.required_without' => 'Cần cung cấp mã voucher hoặc chương trình khuyến mãi.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'amount' => ['description' => 'Giá trị đơn hàng trước khuyến mãi.', 'example' => 6000000],
            'promotion_id' => ['description' => 'Chương trình áp dụng (nếu không dùng voucher).', 'example' => 1],
            'voucher_code' => ['description' => 'Mã voucher áp dụng.', 'example' => 'HANA2026AB'],
            'enrollment_id' => ['description' => 'Lượt ghi danh liên quan.', 'example' => 1],
            'invoice_id' => ['description' => 'Hóa đơn liên quan.', 'example' => 1],
            'customer_id' => ['description' => 'Khách hàng liên quan.', 'example' => 1],
        ];
    }
}
