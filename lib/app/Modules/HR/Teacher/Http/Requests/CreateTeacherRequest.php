<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam code string required Unique teacher code. Example: T0001
 * @bodyParam name string required Teacher full name. Example: Jane Doe
 * @bodyParam user_id integer Linked user id. Example: 1
 * @bodyParam business_id integer Owning business id. Example: 1
 * @bodyParam type string Teacher type. Example: teacher
 * @bodyParam status string active|inactive. Example: active
 * @bodyParam salary_per_hour number Hourly salary. Example: 150000
 */
class CreateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:hr_teachers,code'],
            'name' => ['required', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'business_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'salary_per_hour' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
