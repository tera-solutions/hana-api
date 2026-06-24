<?php

namespace App\Modules\Education\Evaluation\Http\Requests;

use App\Modules\Education\Evaluation\Enums\EvaluationClassification;
use App\Modules\Education\Evaluation\Enums\EvaluationPeriod;
use App\Modules\Education\Evaluation\Enums\EvaluationType;
use App\Modules\Education\Evaluation\Enums\EvaluatorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluation_type' => ['required', Rule::in(EvaluationType::values())],
            'target_id' => ['required', 'integer', 'min:1'],

            'evaluator_type' => ['required', Rule::in(EvaluatorType::values())],
            'evaluator_id' => ['nullable', 'integer', 'min:1'],

            'course_id' => ['nullable', 'integer', 'exists:edu_courses,id'],
            'class_room_id' => ['nullable', 'integer', 'exists:edu_classes,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:edu_lessons,id'],

            'evaluation_period' => ['required', Rule::in(EvaluationPeriod::values())],

            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.criterion' => ['required', 'string', 'max:100'],
            'criteria.*.score' => ['required', 'numeric', 'between:1,5'],

            // Total score / classification are auto-computed (BR-03); ignore if sent.
            'classification' => ['nullable', Rule::in(EvaluationClassification::values())],
            'comment' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'weaknesses' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],

            'evaluated_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'evaluation_type.required' => 'Loại đánh giá là bắt buộc.',
            'evaluation_type.in' => 'Loại đánh giá không hợp lệ.',
            'target_id.required' => 'Đối tượng đánh giá là bắt buộc.',
            'evaluator_type.required' => 'Loại người đánh giá là bắt buộc.',
            'evaluation_period.required' => 'Kỳ đánh giá là bắt buộc.',
            'criteria.required' => 'Cần ít nhất một tiêu chí đánh giá.',
            'criteria.*.criterion.required' => 'Tên tiêu chí là bắt buộc.',
            'criteria.*.score.required' => 'Điểm tiêu chí là bắt buộc.',
            'criteria.*.score.between' => 'Điểm tiêu chí phải từ 1 đến 5.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'evaluation_type' => ['description' => 'Loại đánh giá: teacher | student | parent.', 'example' => 'student'],
            'target_id' => ['description' => 'ID đối tượng được đánh giá (giáo viên / học viên / phụ huynh theo loại).', 'example' => 1],
            'evaluator_type' => ['description' => 'Loại người đánh giá: parent | student | manager | teacher | cskh.', 'example' => 'teacher'],
            'evaluator_id' => ['description' => 'ID người đánh giá.', 'example' => 1],
            'course_id' => ['description' => 'Khóa học liên quan (tùy chọn).', 'example' => 1],
            'class_room_id' => ['description' => 'Lớp học liên quan (tùy chọn).', 'example' => 1],
            'lesson_id' => ['description' => 'Bài học liên quan (tùy chọn).', 'example' => 1],
            'evaluation_period' => ['description' => 'Kỳ đánh giá: session | lesson | midterm | final | course | monthly | quarterly.', 'example' => 'final'],
            'criteria' => ['description' => 'Danh sách tiêu chí và điểm (1-5). Điểm tổng được tính tự động.', 'example' => [['criterion' => 'knowledge', 'score' => 5], ['criterion' => 'grammar', 'score' => 4]]],
            'comment' => ['description' => 'Nhận xét chung.', 'example' => 'Tiến bộ rõ rệt.'],
            'strengths' => ['description' => 'Điểm mạnh.', 'example' => 'Phát âm tốt.'],
            'weaknesses' => ['description' => 'Điểm cần cải thiện.', 'example' => 'Cần luyện ngữ pháp.'],
            'recommendations' => ['description' => 'Khuyến nghị.', 'example' => 'Làm thêm bài tập về nhà.'],
            'evaluated_at' => ['description' => 'Ngày đánh giá (mặc định hiện tại).', 'example' => '2026-06-24'],
        ];
    }
}
