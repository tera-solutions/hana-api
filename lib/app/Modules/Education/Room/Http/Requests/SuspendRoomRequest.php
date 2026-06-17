<?php

namespace App\Modules\Education\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            // room.md BR005: confirm when the room has future sessions scheduled.
            'force' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Lý do là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => [
                'description' => 'Reason for suspending the room.',
                'example' => 'Phòng đang sửa chữa',
            ],
            'force' => [
                'description' => 'Confirm suspension even when future sessions are scheduled (room.md BR005).',
                'example' => false,
            ],
        ];
    }
}
