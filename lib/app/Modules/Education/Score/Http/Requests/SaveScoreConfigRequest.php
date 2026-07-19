<?php

namespace App\Modules\Education\Score\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveScoreConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'components' => ['required', 'array', 'min:1'],
            'components.*.key' => ['required', 'string', 'max:50'],
            'components.*.label' => ['required', 'string', 'max:100'],
            'components.*.weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ];
    }
}
