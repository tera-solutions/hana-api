<?php

namespace App\Modules\Education\Room\Http\Requests;

use App\Modules\Education\Room\Enums\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:sys_branches,id'],
            // room.md BR001: code must be unique within the branch.
            'room_code' => [
                'required', 'string', 'max:255',
                Rule::unique('edu_rooms', 'room_code')->where('branch_id', $this->input('branch_id')),
            ],
            'room_name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'floor' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1'],
            'room_type' => ['required', Rule::in(RoomType::values())],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Chi nhánh là bắt buộc.',
            'branch_id.exists' => 'Chi nhánh không tồn tại.',
            'room_code.required' => 'Mã phòng là bắt buộc.',
            'room_code.unique' => 'Mã phòng đã tồn tại trong chi nhánh này.',
            'room_name.required' => 'Tên phòng là bắt buộc.',
            'capacity.required' => 'Sức chứa là bắt buộc.',
            'capacity.min' => 'Sức chứa phải lớn hơn 0.',
            'room_type.required' => 'Loại phòng là bắt buộc.',
            'room_type.in' => 'Loại phòng không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'branch_id' => [
                'description' => 'Owning branch id.',
                'example' => 1,
            ],
            'room_code' => [
                'description' => 'Room code, unique within the branch.',
                'example' => 'A101',
            ],
            'room_name' => [
                'description' => 'Room name.',
                'example' => 'Phòng A101',
            ],
            'avatar' => [
                'description' => 'Avatar URL.',
                'example' => 'https://cdn.hana.edu.vn/a.png',
            ],
            'floor' => [
                'description' => 'Floor.',
                'example' => '1',
            ],
            'capacity' => [
                'description' => 'Maximum capacity (> 0).',
                'example' => 25,
            ],
            'room_type' => [
                'description' => 'Room type: classroom, computer_room, speaking_room, exam_room, meeting_room, other.',
                'example' => 'classroom',
            ],
            'description' => [
                'description' => 'Notes.',
            ],
        ];
    }
}
