<?php

namespace App\Modules\Education\Timetable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:edu_rooms,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Vui lòng chọn phòng học mới.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'room_id' => ['description' => 'Phòng học mới.', 'example' => 3],
            'reason' => ['description' => 'Lý do đổi phòng.', 'example' => 'Phòng A đang sửa chữa.'],
        ];
    }
}
