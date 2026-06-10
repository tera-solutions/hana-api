<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam code string Unique teacher code. Example: T0001
 * @bodyParam name string Teacher full name. Example: Jane Doe
 * @bodyParam user_id integer Linked user id. Example: 1
 * @bodyParam business_id integer Owning business id. Example: 1
 * @bodyParam type string Teacher type. Example: teacher
 * @bodyParam status string active|inactive. Example: active
 * @bodyParam salary_per_hour number Hourly salary. Example: 150000
 */
class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('hr_teachers', 'code')->ignore($id)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'business_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'salary_per_hour' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
