<?php

namespace App\Modules\System\SocialAuth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(['google', 'microsoft'])],
            'id_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider.required' => 'Nhà cung cấp đăng nhập là bắt buộc.',
            'provider.in' => 'Nhà cung cấp đăng nhập không được hỗ trợ.',
            'id_token.required' => 'Thiếu id_token.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'provider' => ['description' => 'OAuth provider: google | microsoft.', 'example' => 'google'],
            'id_token' => ['description' => 'The provider-issued id_token (JWT) from Google Identity Services / MSAL.js.', 'example' => 'eyJhbGciOiJSUzI1NiIs...'],
        ];
    }
}
