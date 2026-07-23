<?php

namespace App\Modules\Education\Material\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Entities a material can be linked to (material.md §9, §15).
 */
enum MaterialEntityType: string implements HasLabel
{
    use ProvidesOptions;

    case Course = 'course';
    case LessonPlan = 'lesson_plan';
    case Lesson = 'lesson';
    case Assignment = 'assignment';
    case Evaluation = 'evaluation';
    case Exam = 'exam';

    public function label(): string
    {
        return match ($this) {
            self::Course => 'Khóa học',
            self::LessonPlan => 'Giáo án',
            self::Lesson => 'Buổi học',
            self::Assignment => 'Bài tập',
            self::Evaluation => 'Đánh giá',
            self::Exam => 'Bài kiểm tra',
        };
    }

    /**
     * The table the entity_id refers to (used to verify the link target exists).
     */
    public function table(): string
    {
        return match ($this) {
            self::Course => 'edu_courses',
            self::LessonPlan => 'edu_lesson_plans',
            self::Lesson => 'edu_lessons',
            self::Assignment => 'edu_assignments',
            self::Evaluation => 'edu_evaluations',
            self::Exam => 'edu_exams',
        };
    }
}
