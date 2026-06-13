<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Lead and Student are immutable and ignored if sent; only the relationship is
 * editable (lead.md §9).
 */
class UpdateLeadStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'relationship' => [
                'sometimes', 'nullable', 'string',
                'in:father,mother,guardian,grandfather,grandmother,uncle,aunt,other',
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'relationship' => ['description' => 'father|mother|guardian|grandfather|grandmother|uncle|aunt|other.', 'example' => 'mother'],
        ];
    }
}
