<?php

namespace App\Modules\CRM\Parent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Parent id, code, business and status are immutable and ignored if sent.
 *
 * @bodyParam name string Full name. Example: Robert Smith
 * @bodyParam gender string male|female|other. Example: male
 * @bodyParam dob date Date of birth (<= today). Example: 1985-03-20
 * @bodyParam avatar string Avatar URL.
 * @bodyParam email string Contact email. Example: robert@example.com
 * @bodyParam phone string Contact phone. Example: 0922222222
 * @bodyParam address string Address. Example: 123 Le Loi
 * @bodyParam province string Province / city. Example: Ho Chi Minh
 * @bodyParam district string District. Example: District 7
 * @bodyParam branch_id integer Branch id. Example: 1
 * @bodyParam occupation string Occupation. Example: Engineer
 * @bodyParam company string Company. Example: ABC Corp
 * @bodyParam note string Note.
 * @bodyParam students object[] Students to link (replaces existing links).
 * @bodyParam students[].student_id integer required Existing student id. Example: 1
 * @bodyParam students[].relation string father|mother|guardian|grandfather|grandmother|other. Example: father
 */
class UpdateParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dob' => ['nullable', 'date', 'before_or_equal:today'],
            'avatar' => ['nullable', 'string', 'max:1000'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],

            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],

            'students' => ['nullable', 'array'],
            'students.*.student_id' => ['required', 'integer', 'exists:edu_students,id'],
            'students.*.relation' => ['nullable', 'string', 'in:father,mother,guardian,grandfather,grandmother,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'dob.before_or_equal' => 'Ngày sinh phải nhỏ hơn hoặc bằng ngày hiện tại.',
        ];
    }
}
