<?php

namespace App\Modules\Education\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam reason string required Reason for suspending the course. Example: Tạm dừng tuyển sinh
 */
class SuspendCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
