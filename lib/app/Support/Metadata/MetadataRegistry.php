<?php

namespace App\Support\Metadata;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\RefundStatus;
use App\Enums\Shared\Gender;
use App\Enums\Shared\GuardianRelation;
use App\Modules\CRM\Lead\Enums\LeadStatus;
use App\Modules\CRM\Parent\Enums\ParentStatus;
use App\Modules\Education\Assignment\Enums\AssignmentStatus;
use App\Modules\Education\Assignment\Enums\AssignmentType;
use App\Modules\Education\Assignment\Enums\SubmissionStatus;
use App\Modules\Education\Attendance\Enums\AttendanceStatus;
use App\Modules\Education\ClassRoom\Enums\ClassLearningType;
use App\Modules\Education\ClassRoom\Enums\ClassStatus;
use App\Modules\Education\ClassRoom\Enums\ClassStudentStatus;
use App\Modules\Education\ClassSession\Enums\ClassSessionStatus;
use App\Modules\Education\Enrollment\Enums\EnrollmentStatus;
use App\Modules\Education\Evaluation\Enums\EvaluationClassification;
use App\Modules\Education\Evaluation\Enums\EvaluationPeriod;
use App\Modules\Education\Evaluation\Enums\EvaluationStatus;
use App\Modules\Education\Evaluation\Enums\EvaluationType;
use App\Modules\Education\Evaluation\Enums\EvaluatorType;
use App\Modules\Education\Exam\Enums\ExamSessionStatus;
use App\Modules\Education\Exam\Enums\ExamSkill;
use App\Modules\Education\Exam\Enums\ExamStatus;
use App\Modules\Education\Exam\Enums\ExamType;
use App\Modules\Education\Exam\Enums\QuestionDifficulty as ExamQuestionDifficulty;
use App\Modules\Education\Exam\Enums\QuestionType as ExamQuestionType;
use App\Modules\Education\Exam\Enums\RegistrationStatus;
use App\Modules\Education\LeaveRequest\Enums\LeaveReasonType;
use App\Modules\Education\LeaveRequest\Enums\LeaveRequestType;
use App\Modules\Education\LeaveRequest\Enums\LeaveStatus;
use App\Modules\Education\LeaveRequest\Enums\MakeupStatus;
use App\Modules\Education\Lesson\Enums\LessonStatus;
use App\Modules\Education\LessonPlan\Enums\LessonPlanStatus;
use App\Modules\Education\LessonPlanMaterial\Enums\MaterialType as LessonPlanMaterialType;
use App\Modules\Education\Material\Enums\MaterialAccessType;
use App\Modules\Education\Material\Enums\MaterialEntityType;
use App\Modules\Education\Material\Enums\MaterialStatus;
use App\Modules\Education\Material\Enums\MaterialType;
use App\Modules\Education\Question\Enums\QuestionDifficulty;
use App\Modules\Education\Question\Enums\QuestionSkill;
use App\Modules\Education\Question\Enums\QuestionStatus;
use App\Modules\Education\Question\Enums\QuestionType;
use App\Modules\Education\Room\Enums\RoomStatus;
use App\Modules\Education\Room\Enums\RoomType;
use App\Modules\Education\Student\Enums\StudentStatus;
use App\Modules\Education\Timetable\Enums\SchedulePattern;
use App\Modules\Education\Timetable\Enums\TimetableStatus;
use App\Modules\Finance\Account\Enums\AccountType;
use App\Modules\Finance\Debt\Enums\AdjustmentType;
use App\Modules\Finance\Debt\Enums\DebtStatus;
use App\Modules\Finance\Invoice\Enums\InvoiceStatus;
use App\Modules\Finance\Invoice\Enums\InvoiceType;
use App\Modules\Finance\Invoice\Enums\PartnerType;
use App\Modules\Finance\Payment\Enums\PaymentDirection;
use App\Modules\Finance\Payment\Enums\PaymentStatus;
use App\Modules\Finance\Payment\Enums\PaymentType;
use App\Modules\Finance\Promotion\Enums\DiscountType;
use App\Modules\Finance\Promotion\Enums\PromotionStatus;
use App\Modules\Finance\Promotion\Enums\PromotionType;
use App\Modules\Finance\Promotion\Enums\ReferralStatus;
use App\Modules\Finance\Promotion\Enums\VoucherStatus;
use App\Modules\Finance\Wallet\Enums\WalletAdjustmentType;
use App\Modules\Finance\Wallet\Enums\WalletOwnerType;
use App\Modules\Finance\Wallet\Enums\WalletStatus;
use App\Modules\Finance\Wallet\Enums\WalletTransactionType;
use App\Modules\HR\Teacher\Enums\TeacherStatus;
use App\Modules\HR\Teacher\Enums\TeacherType;
use App\Modules\System\ActivityLog\Enums\ActivityAction;
use App\Modules\System\ActivityLog\Enums\ActivityModule;
use App\Modules\System\Branch\Enums\BranchStatus;
use App\Modules\System\Business\Enums\BusinessStatus;
use App\Modules\System\User\Enums\UserStatus;

/**
 * Single source of truth for the enumerations the frontend needs to render
 * dropdowns, status badges and filters. Grouped by domain; every leaf is a list
 * of `{ value, label }` produced from a backed enum, so there is exactly one
 * place to add or rename an option.
 */
class MetadataRegistry
{
    /**
     * The full catalog: `domain => field => [{value, label}, ...]`.
     *
     * @return array<string, array<string, array<int, array{key: string, value: int|string, label: string}>>>
     */
    public static function all(): array
    {
        return [
            'shared' => [
                'gender' => Gender::options(),
                'guardian_relation' => GuardianRelation::options(),
            ],
            'system' => [
                'user_status' => UserStatus::options(),
                'business_status' => BusinessStatus::options(),
                'branch_status' => BranchStatus::options(),
                'activity_action' => ActivityAction::options(),
                'activity_module' => ActivityModule::options(),
            ],
            'crm' => [
                'lead_status' => LeadStatus::options(),
                'parent_status' => ParentStatus::options(),
            ],
            'education' => [
                'student_status' => StudentStatus::options(),
                'class_status' => ClassStatus::options(),
                'class_learning_type' => ClassLearningType::options(),
                'class_student_status' => ClassStudentStatus::options(),
                'class_session_status' => ClassSessionStatus::options(),
                'attendance_status' => AttendanceStatus::options(),
                'enrollment_status' => EnrollmentStatus::options(),
                'room_status' => RoomStatus::options(),
                'room_type' => RoomType::options(),
                'lesson_plan_status' => LessonPlanStatus::options(),
                'lesson_status' => LessonStatus::options(),
                'evaluation_type' => EvaluationType::options(),
                'evaluator_type' => EvaluatorType::options(),
                'evaluation_period' => EvaluationPeriod::options(),
                'evaluation_classification' => EvaluationClassification::options(),
                'evaluation_status' => EvaluationStatus::options(),
                'lesson_plan_material_type' => LessonPlanMaterialType::options(),

                'material_type' => MaterialType::options(),
                'material_status' => MaterialStatus::options(),
                'material_access_type' => MaterialAccessType::options(),
                'material_entity_type' => MaterialEntityType::options(),

                'assignment_status' => AssignmentStatus::options(),
                'assignment_type' => AssignmentType::options(),
                'assignment_submission_status' => SubmissionStatus::options(),

                'question_status' => QuestionStatus::options(),
                'question_type' => QuestionType::options(),
                'question_skill' => QuestionSkill::options(),
                'question_difficulty' => QuestionDifficulty::options(),

                'exam_status' => ExamStatus::options(),
                'exam_type' => ExamType::options(),
                'exam_skill' => ExamSkill::options(),
                'exam_question_type' => ExamQuestionType::options(),
                'exam_question_difficulty' => ExamQuestionDifficulty::options(),
                'exam_session_status' => ExamSessionStatus::options(),
                'exam_registration_status' => RegistrationStatus::options(),

                'leave_status' => LeaveStatus::options(),
                'leave_request_type' => LeaveRequestType::options(),
                'leave_reason_type' => LeaveReasonType::options(),
                'makeup_status' => MakeupStatus::options(),

                'timetable_status' => TimetableStatus::options(),
                'schedule_pattern' => SchedulePattern::options(),
            ],
            'hr' => [
                'teacher_status' => TeacherStatus::options(),
                'teacher_type' => TeacherType::options(),
            ],
            'finance' => [
                'invoice_type' => InvoiceType::options(),
                'invoice_status' => InvoiceStatus::options(),
                'partner_type' => PartnerType::options(),
                'payment_direction' => PaymentDirection::options(),
                'payment_status' => PaymentStatus::options(),
                'payment_type' => PaymentType::options(),
                'payment_method' => PaymentMethod::options(),
                'account_type' => AccountType::options(),
                'debt_status' => DebtStatus::options(),
                'adjustment_type' => AdjustmentType::options(),
                'refund_status' => RefundStatus::options(),
                'wallet_owner_type' => WalletOwnerType::options(),
                'wallet_status' => WalletStatus::options(),
                'wallet_transaction_type' => WalletTransactionType::options(),
                'wallet_adjustment_type' => WalletAdjustmentType::options(),

                'promotion_status' => PromotionStatus::options(),
                'promotion_type' => PromotionType::options(),
                'discount_type' => DiscountType::options(),
                'voucher_status' => VoucherStatus::options(),
                'referral_status' => ReferralStatus::options(),
            ],
        ];
    }
}
