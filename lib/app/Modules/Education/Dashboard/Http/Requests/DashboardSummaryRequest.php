<?php

namespace App\Modules\Education\Dashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.date_format' => 'Ngày phải có định dạng Y-m-d.',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'date' => [
                'description' => 'Ngày làm mốc cho "hôm nay" và tuần ISO (mặc định là hôm nay của server).',
                'example' => '2026-07-01',
            ],
        ];
    }
}
