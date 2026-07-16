<?php

namespace App\Modules\System\Onboarding\Http\Requests;

use App\Enums\Shared\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Owner account.
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:sys_business,email'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'gender' => ['nullable', 'string', Rule::in(Gender::values())],
            'dob' => ['nullable', 'date'],
            'avatar' => ['nullable', 'string', 'max:255'],

            // Center (business) + teacher profile.
            'school' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'subject' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],

            // Sent by the client to identify the app; not persisted.
            'app_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'email.unique' => 'Email đã tồn tại.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'phone.unique' => 'Số điện thoại đã tồn tại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'dob.date' => 'Ngày sinh không hợp lệ.',
            'school.required' => 'Vui lòng nhập tên trường/trung tâm.',
            'experience.integer' => 'Số năm kinh nghiệm không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'full_name' => ['description' => 'Owner full name.', 'example' => 'Nguyen Van A'],
            'email' => ['description' => 'Owner login email (unique).', 'example' => 'owner@hana.edu.vn'],
            'phone' => ['description' => 'Owner phone (unique).', 'example' => '0901234567'],
            'password' => ['description' => 'Min 8, upper/lower/number.', 'example' => 'Abc@1234'],
            'password_confirmation' => ['description' => 'Must match password.', 'example' => 'Abc@1234'],
            'gender' => ['description' => 'male|female|other.', 'example' => 'male'],
            'dob' => ['description' => 'Date of birth (Y-m-d).', 'example' => '1995-05-20'],
            'avatar' => ['description' => 'Avatar path/URL.', 'example' => 'avatars/a.png'],
            'school' => ['description' => 'Center / school name.', 'example' => 'Hana English'],
            'position' => ['description' => 'Teaching position.', 'example' => 'Giáo viên IELTS'],
            'experience' => ['description' => 'Years of experience.', 'example' => 5],
            'subject' => ['description' => 'Main subject.', 'example' => 'Tiếng Anh'],
            'bio' => ['description' => 'Short biography.', 'example' => 'Giáo viên với 5 năm kinh nghiệm.'],
        ];
    }
}
