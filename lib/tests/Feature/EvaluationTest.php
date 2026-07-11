<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $courseId;

    private int $classId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId();
        $this->courseId = $this->makeCourseId();
        $this->classId = $this->makeClassId();
    }

    private function makeBranchId(): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'BR_'.strtoupper(uniqid()),
            'address' => '1 Test St',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Course '.uniqid(),
            'code' => 'C_'.strtoupper(uniqid()),
            'duration_minutes' => 90,
            'price_per_lesson' => 100000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassId(): int
    {
        return DB::table('edu_classes')->insertGetId([
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'Eval Class',
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'learning_type' => 'flexible',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeStudentId(): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeTeacherId(): int
    {
        return DB::table('hr_teachers')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Teacher '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeLessonId(): int
    {
        static $no = 0;
        $no++;

        return DB::table('edu_lessons')->insertGetId([
            'class_room_id' => $this->classId,
            'lesson_no' => $no,
            'lesson_title' => 'Lesson '.uniqid(),
            'lesson_date' => now()->toDateString(),
            'start_time' => '19:00',
            'end_time' => '20:30',
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function studentEvaluationPayload(array $overrides = []): array
    {
        return array_merge([
            'evaluation_type' => 'student',
            'target_id' => $this->makeStudentId(),
            'evaluator_type' => 'teacher',
            'evaluator_id' => $this->makeTeacherId(),
            'course_id' => $this->courseId,
            'class_room_id' => $this->classId,
            'evaluation_period' => 'final',
            'criteria' => [
                ['criterion' => 'knowledge', 'score' => 5],
                ['criterion' => 'grammar', 'score' => 4],
            ],
            'comment' => 'Tiến bộ tốt.',
        ], $overrides);
    }

    private function createEvaluation(array $overrides = []): int
    {
        return $this->postJson('/v1/edu/evaluation/create', $this->studentEvaluationPayload($overrides))->json('data.id');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/evaluation/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/evaluation/list')->assertJsonPath('code', 403);
    }

    public function test_create_computes_score_and_classification(): void
    {
        $this->actingAsAdmin();

        // avg(5, 4) = 4.50 -> "excellent" (>= 4.5).
        $this->postJson('/v1/edu/evaluation/create', $this->studentEvaluationPayload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.evaluation_code', 'EVAL000001')
            ->assertJsonPath('data.score', '4.50')
            ->assertJsonPath('data.classification', 'excellent');
    }

    public function test_create_rejects_criterion_not_valid_for_type(): void
    {
        $this->actingAsAdmin();

        // "expertise" is a teacher criterion, not a student one.
        $this->postJson('/v1/edu/evaluation/create', $this->studentEvaluationPayload([
            'criteria' => [['criterion' => 'expertise', 'score' => 5]],
        ]))
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Tiêu chí "expertise" không hợp lệ cho loại đánh giá này.');
    }

    public function test_create_validates_score_range(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/evaluation/create', $this->studentEvaluationPayload([
            'criteria' => [['criterion' => 'knowledge', 'score' => 9]],
        ]))->assertStatus(422)->assertJsonValidationErrors(['criteria.0.score']);
    }

    public function test_duplicate_in_same_period_is_rejected(): void
    {
        $this->actingAsAdmin();

        $studentId = $this->makeStudentId();
        $teacherId = $this->makeTeacherId();

        $payload = $this->studentEvaluationPayload(['target_id' => $studentId, 'evaluator_id' => $teacherId]);

        $this->postJson('/v1/edu/evaluation/create', $payload)->assertJsonPath('success', true);

        $this->postJson('/v1/edu/evaluation/create', $payload)
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Đã tồn tại đánh giá cho đối tượng này trong cùng kỳ.');
    }

    public function test_cannot_evaluate_self(): void
    {
        $this->actingAsAdmin();

        $teacherId = $this->makeTeacherId();

        // A teacher evaluating the same teacher id is self-evaluation.
        $this->postJson('/v1/edu/evaluation/create', $this->studentEvaluationPayload([
            'evaluation_type' => 'teacher',
            'target_id' => $teacherId,
            'evaluator_type' => 'teacher',
            'evaluator_id' => $teacherId,
            'criteria' => [['criterion' => 'expertise', 'score' => 5]],
        ]))
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Không thể tự đánh giá chính mình.');
    }

    public function test_same_id_different_type_is_allowed(): void
    {
        $this->actingAsAdmin();

        // A teacher (id N) evaluating a student that happens to also have id N is fine.
        $this->postJson('/v1/edu/evaluation/create', $this->studentEvaluationPayload([
            'target_id' => 1,
            'evaluator_type' => 'teacher',
            'evaluator_id' => 1,
        ]))->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_update_recomputes_score(): void
    {
        $this->actingAsAdmin();

        $id = $this->createEvaluation();

        // avg(2, 3) = 2.50 -> "average".
        $this->putJson("/v1/edu/evaluation/update/{$id}", [
            'criteria' => [
                ['criterion' => 'knowledge', 'score' => 2],
                ['criterion' => 'grammar', 'score' => 3],
            ],
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.score', '2.50')
            ->assertJsonPath('data.classification', 'average');
    }

    public function test_lifecycle_submit_approve_lock_then_edit_is_blocked(): void
    {
        $this->actingAsAdmin();

        $id = $this->createEvaluation();

        $this->postJson("/v1/edu/evaluation/submit/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        $this->postJson("/v1/edu/evaluation/approve/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/v1/edu/evaluation/lock/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'locked');

        // BR-02: locked evaluations cannot be edited or deleted.
        $this->putJson("/v1/edu/evaluation/update/{$id}", ['comment' => 'x'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Không thể sửa đánh giá đã khóa.');

        $this->deleteJson("/v1/edu/evaluation/delete/{$id}")
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Không thể xóa đánh giá đã khóa.');
    }

    public function test_cannot_approve_a_draft(): void
    {
        $this->actingAsAdmin();

        $id = $this->createEvaluation();

        $this->postJson("/v1/edu/evaluation/approve/{$id}")
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Chỉ có thể duyệt đánh giá đã gửi.');
    }

    public function test_rejected_evaluation_can_be_resubmitted(): void
    {
        $this->actingAsAdmin();

        $id = $this->createEvaluation();

        $this->postJson("/v1/edu/evaluation/submit/{$id}")->assertJsonPath('data.status', 'submitted');
        $this->postJson("/v1/edu/evaluation/reject/{$id}")->assertJsonPath('data.status', 'rejected');
        $this->postJson("/v1/edu/evaluation/submit/{$id}")->assertJsonPath('data.status', 'submitted');
    }

    public function test_delete_soft_deletes(): void
    {
        $this->actingAsAdmin();

        $id = $this->createEvaluation();

        $this->deleteJson("/v1/edu/evaluation/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('edu_evaluations', ['id' => $id]);
    }

    public function test_list_filters_by_type(): void
    {
        $this->actingAsAdmin();

        $this->createEvaluation();
        $this->postJson('/v1/edu/evaluation/create', $this->studentEvaluationPayload([
            'evaluation_type' => 'teacher',
            'target_id' => $this->makeTeacherId(),
            'evaluator_type' => 'student',
            'evaluator_id' => $this->makeStudentId(),
            'criteria' => [['criterion' => 'expertise', 'score' => 5]],
        ]))->assertJsonPath('success', true);

        $this->getJson('/v1/edu/evaluation/list?evaluation_type=teacher')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.evaluation_type', 'teacher');
    }
}
