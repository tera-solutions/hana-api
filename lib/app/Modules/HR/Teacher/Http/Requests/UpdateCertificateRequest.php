<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update a teacher's certificate.
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

    public function bodyParameters(): array
    {
        return [
            'certificate_name' => ['description' => 'Certificate name.', 'example' => 'IELTS 8.5'],
            'issuer' => ['description' => 'Issuing organisation.', 'example' => 'British Council'],
            'issued_date' => ['description' => 'Issue date.', 'example' => '2024-03-01'],
            'expired_date' => ['description' => 'Expiry date (>= issued_date).', 'example' => '2027-03-01'],
            'attachment' => ['description' => 'Attachment file path.', 'example' => 'certificates/ielts.pdf'],
        ];
    }
}
