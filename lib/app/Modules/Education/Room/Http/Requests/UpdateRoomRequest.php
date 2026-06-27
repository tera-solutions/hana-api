<?php

namespace App\Modules\Education\Room\Http\Requests;

use App\Modules\Education\Room\Enums\RoomType;
use App\Modules\Education\Room\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Branch is fixed after creation; room_code becomes immutable once classes exist
 * (stripped in the service in that case). Status changes go through suspend/restore.
 */
class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $branchId = Room::where('id', $id)->value('branch_id');

        return [
            'room_code' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('edu_rooms', 'room_code')->where('branch_id', $branchId)->ignore($id),
            ],
            'room_name' => ['sometimes', 'required', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:50'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'room_type' => ['sometimes', 'required', Rule::in(RoomType::values())],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_code.unique' => 'Mã phòng đã tồn tại trong chi nhánh này.',
            'room_name.required' => 'Tên phòng là bắt buộc.',
            'capacity.min' => 'Sức chứa phải lớn hơn 0.',
            'room_type.in' => 'Loại phòng không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'room_code' => [
                'description' => 'Room code, unique within the branch (immutable once classes exist).',
                'example' => 'A101',
            ],
            'room_name' => [
                'description' => 'Room name.',
                'example' => 'Phòng A101',
            ],
            'floor' => [
                'description' => 'Floor.',
                'example' => '1',
            ],
            'capacity' => [
                'description' => 'Maximum capacity (> 0). Cannot drop below students in active classes.',
                'example' => 30,
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
