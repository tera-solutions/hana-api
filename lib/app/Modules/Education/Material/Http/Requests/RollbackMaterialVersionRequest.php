<?php

namespace App\Modules\Education\Material\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RollbackMaterialVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'version.required' => 'Phiên bản là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'version' => ['description' => 'Version number to roll back to.', 'example' => 1],
        ];
    }
}
