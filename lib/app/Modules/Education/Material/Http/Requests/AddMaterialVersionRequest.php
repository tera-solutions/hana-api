<?php

namespace App\Modules\Education\Material\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A new version (material.md §8). Real file upload/storage is not implemented in
 * this codebase; the file is referenced by id + metadata.
 */
class AddMaterialVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_id' => ['nullable', 'integer', 'min:1'],
            'file_name' => ['required', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'change_log' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_name.required' => 'Tên file là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'file_id' => ['description' => 'Reference to the uploaded file.', 'example' => 11],
            'file_name' => ['description' => 'Original file name.', 'example' => 'workbook-starter-v2.pdf'],
            'file_size' => ['description' => 'File size in bytes.', 'example' => 2097152],
            'mime_type' => ['description' => 'File mime type.', 'example' => 'application/pdf'],
            'change_log' => ['description' => 'What changed in this version.', 'example' => 'Bổ sung chương 5'],
        ];
    }
}
