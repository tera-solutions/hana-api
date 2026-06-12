<?php

namespace App\Modules\Education\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam name string required Full name. Example: Nguyen Van A
 * @bodyParam dob date required Date of birth (<= today). Example: 2010-05-12
 * @bodyParam gender string required male|female|other. Example: male
 * @bodyParam avatar string Avatar URL. Example: https://cdn.hana.edu.vn/a.png
 * @bodyParam nationality string Nationality. Example: Vietnam
 * @bodyParam language string Native language. Example: Vietnamese
 * @bodyParam email string Contact email. Example: a@gmail.com
 * @bodyParam phone string Contact phone. Example: 0901234567
 * @bodyParam business_id integer required Business id. Example: 1
 * @bodyParam branch_id integer required Branch id. Example: 1
 * @bodyParam level string Current level. Example: A1
 * @bodyParam enrollment_date date required Enrollment date. Example: 2026-06-01
 * @bodyParam admission_source string Admission source. Example: Facebook
 * @bodyParam address string Address. Example: 123 Le Loi
 * @bodyParam province string Province / city. Example: Ho Chi Minh
 * @bodyParam district string District. Example: District 7
 * @bodyParam school string School. Example: THPT Le Quy Don
 * @bodyParam grade string Grade. Example: 9
 * @bodyParam note string Note.
 * @bodyParam parents object[] Parents/guardians to assign.
 * @bodyParam parents[].parent_id integer Existing parent id. Example: 1
 * @bodyParam parents[].name string Name (required when no parent_id). Example: Tran Thi B
 * @bodyParam parents[].phone string Parent phone. Example: 0907654321
 * @bodyParam parents[].email string Parent email. Example: b@gmail.com
 * @bodyParam parents[].relation string father|mother|guardian. Example: mother
 */
class CreateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/'],

            'business_id' => ['required', 'integer', 'exists:sys_business,id'],
            'branch_id' => ['required', 'integer', 'exists:sys_branches,id'],
            'level' => ['nullable', 'string', 'max:255'],
            'enrollment_date' => ['required', 'date'],
            'admission_source' => ['nullable', 'string', 'max:255'],

            'address' => ['nullable', 'string', 'max:1000'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],

            'parents' => ['nullable', 'array'],
            'parents.*.parent_id' => ['nullable', 'integer', 'exists:crm_parents,id'],
            'parents.*.name' => ['required_without:parents.*.parent_id', 'nullable', 'string', 'max:255'],
            'parents.*.phone' => ['nullable', 'string', 'max:20'],
            'parents.*.email' => ['nullable', 'email', 'max:255'],
            'parents.*.relation' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'dob.before_or_equal' => 'Ngày sinh phải nhỏ hơn hoặc bằng ngày hiện tại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'parents.*.name.required_without' => 'Tên phụ huynh là bắt buộc khi không chọn phụ huynh có sẵn.',
        ];
    }
}
