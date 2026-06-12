<?php

namespace App\Modules\Education\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam stop_date date required Suspension date. Example: 2026-06-12
 * @bodyParam reason string required Reason for suspension. Example: Nghỉ dài hạn
 * @bodyParam note string Additional note.
 */
class SuspendStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stop_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
