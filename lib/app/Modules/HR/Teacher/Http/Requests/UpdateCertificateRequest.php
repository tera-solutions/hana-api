<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam certificate_name string Certificate name. Example: IELTS 8.0
 * @bodyParam issuer string Issuing organisation. Example: British Council
 * @bodyParam issued_date date Issue date. Example: 2024-03-01
 * @bodyParam expired_date date Expiry date. Example: 2027-03-01
 * @bodyParam attachment string Attachment URL.
 */
class UpdateCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'certificate_name' => ['sometimes', 'required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['nullable', 'date'],
            'expired_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'attachment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
