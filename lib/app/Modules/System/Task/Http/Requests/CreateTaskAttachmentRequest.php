<?php

namespace App\Modules\System\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_id' => ['required', 'integer', 'exists:media,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_id.required' => 'Tệp đính kèm là bắt buộc.',
            'file_id.exists' => 'Tệp đính kèm không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'file_id' => ['description' => 'Media id của tệp đã tải lên (PDF/Word/Excel/ảnh).', 'example' => 10],
        ];
    }
}
