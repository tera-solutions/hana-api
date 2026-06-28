<?php

namespace Tests\Feature;

use Database\Seeders\AssignmentPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AssignmentPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId();
        $this->courseId = $this->makeCourseId();
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

    private function makeStudentId(array $overrides = []): int
    {
        return DB::table('edu_students')->insertGetId(array_merge([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function makeLevelId(): int
    {
        return DB::table('edu_levels')->insertGetId([
            'level_code' => 'L_'.strtoupper(uniqid()),
            'level_name' => 'L_'.strtoupper(uniqid()),
            'level_order' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassId(): int
    {
        return DB::table('edu_classes')->insertGetId([
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'Assignment Class',
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'learning_type' => 'flexible',
            'status' => 'upcoming',
            'start_date' => now()->addDays(7)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function enroll(int $classId, int $studentId, string $status = 'active'): void
    {
        DB::table('edu_class_students')->insert([
            'class_id' => $classId,
            'student_id' => $studentId,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeLessonId(int $classId): int
    {
        return DB::table('edu_lessons')->insertGetId([
            'class_room_id' => $classId,
            'lesson_no' => 1,
            'lesson_title' => 'Lesson '.uniqid(),
            'lesson_date' => now()->addDays(1)->toDateString(),
            'start_time' => '19:00',
            'end_time' => '20:30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'assignment_name' => 'Unit 1 Homework',
            'assignment_type' => 'homework',
            'instruction' => 'Hoàn thành bài tập trang 10.',
            'max_score' => 10,
            'due_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    private function createAssignment(array $overrides = []): int
    {
        return $this->postJson('/v1/edu/assignment/create', $this->payload($overrides))->json('data.id');
    }

    private function publish(int $id): TestResponse
    {
        return $this->postJson("/v1/edu/assignment/publish/{$id}");
    }

    private function assignByStudent(int $id, array $studentIds): TestResponse
    {
        return $this->postJson("/v1/edu/assignment/assign/student/{$id}", ['student_ids' => $studentIds]);
    }

    private function assignByClass(int $id, int $classRoomId): TestResponse
    {
        return $this->postJson("/v1/edu/assignment/assign/class/{$id}", ['class_room_id' => $classRoomId]);
    }

    private function assignByGroup(int $id, int $levelId): TestResponse
    {
        return $this->postJson("/v1/edu/assignment/assign/group/{$id}", ['level_id' => $levelId]);
    }

    private function assignByLesson(int $id, int $lessonId): TestResponse
    {
        return $this->postJson("/v1/edu/assignment/assign/lesson/{$id}", ['lesson_id' => $lessonId]);
    }

    private function submit(int $id, int $studentId, array $extra = []): TestResponse
    {
        return $this->postJson("/v1/edu/assignment/submit/{$id}", array_merge(['student_id' => $studentId], $extra));
    }

    private function submissionId(int $assignmentId, int $studentId): int
    {
        return (int) DB::table('edu_assignment_submissions')
            ->where('assignment_id', $assignmentId)
            ->where('student_id', $studentId)
            ->value('id');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/assignment/list')->assertJsonPath('code', 401);
    }

    public function test_create_starts_as_draft(): void
    {
        $this->actingAsAdmin();

        // assignment_code is auto-generated (spec §VI omits it from create input).
        $this->postJson('/v1/edu/assignment/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('data.assignment_code', 'ASG000001')
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_create_validation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/assignment/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['assignment_name', 'assignment_type', 'instruction', 'max_score', 'due_date']);

        // BR002 (past due) + BR003 (max_score <= 0).
        $this->postJson('/v1/edu/assignment/create', $this->payload([
            'due_date' => now()->subDay()->format('Y-m-d H:i:s'),
            'max_score' => 0,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['due_date', 'max_score']);
    }

    public function test_delete_soft_deletes_assignment(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();

        $this->deleteJson("/v1/edu/assignment/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('edu_assignments', ['id' => $id]);

        $this->getJson("/v1/edu/assignment/detail/{$id}")
            ->assertStatus(404);
    }

    public function test_publish_assign_submit_grade_publish_flow(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();
        $studentId = $this->makeStudentId();

        // Cannot assign before publishing.
        $this->assignByStudent($id, [$studentId])
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->publish($id)
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'published');

        // Assign seeds an ASSIGNED submission (BR004).
        $this->assignByStudent($id, [$studentId])
            ->assertStatus(200)
            ->assertJsonPath('data.assigned', 1);

        $this->assertDatabaseHas('edu_assignment_submissions', [
            'assignment_id' => $id, 'student_id' => $studentId, 'status' => 'assigned',
        ]);

        // Submit.
        $this->submit($id, $studentId, ['answer' => 'My family...'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        $submissionId = $this->submissionId($id, $studentId);

        // Grade (BR008: score within max).
        $this->postJson("/v1/edu/submission/grade/{$submissionId}", ['score' => 9, 'comment' => 'Tốt'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'graded')
            ->assertJsonPath('data.score', '9.00');

        // Publish the result.
        $this->postJson("/v1/edu/submission/publish/{$submissionId}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'reviewed')
            ->assertJsonPath('data.result_published', true);
    }

    public function test_assign_by_class_targets_active_class_students(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();
        $this->publish($id)->assertStatus(200);

        $classId = $this->makeClassId();
        $a = $this->makeStudentId();
        $b = $this->makeStudentId();
        $this->enroll($classId, $a);
        $this->enroll($classId, $b);
        // Dropped enrollment is excluded.
        $this->enroll($classId, $this->makeStudentId(), 'dropped');

        $this->assignByClass($id, $classId)
            ->assertStatus(200)
            ->assertJsonPath('data.assigned', 2);

        $this->assertDatabaseHas('edu_assignment_submissions', [
            'assignment_id' => $id, 'student_id' => $a, 'status' => 'assigned',
        ]);
    }

    public function test_assign_by_group_targets_active_students_of_level(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();
        $this->publish($id)->assertStatus(200);

        $levelId = $this->makeLevelId();
        $this->makeStudentId(['level_id' => $levelId]);
        $this->makeStudentId(['level_id' => $levelId]);
        // Different level + suspended-same-level are excluded.
        $this->makeStudentId(['level_id' => $this->makeLevelId()]);
        $this->makeStudentId(['level_id' => $levelId, 'status' => 'suspended']);

        $this->assignByGroup($id, $levelId)
            ->assertStatus(200)
            ->assertJsonPath('data.assigned', 2);
    }

    public function test_assign_by_lesson_targets_active_students_of_lesson_class(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();
        $this->publish($id)->assertStatus(200);

        $classId = $this->makeClassId();
        $lessonId = $this->makeLessonId($classId);
        $this->enroll($classId, $this->makeStudentId());
        $this->enroll($classId, $this->makeStudentId());
        $this->enroll($classId, $this->makeStudentId(), 'dropped');

        $this->assignByLesson($id, $lessonId)
            ->assertStatus(200)
            ->assertJsonPath('data.assigned', 2);
    }

    public function test_assign_requires_published_assignment(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();

        // BR: only published assignments can be assigned (any target).
        $this->assignByStudent($id, [$this->makeStudentId()])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_only_assigned_student_can_submit(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();
        $this->publish($id)->assertStatus(200);

        $stranger = $this->makeStudentId();

        // BR005: not assigned -> rejected.
        $this->submit($id, $stranger)
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_grade_cannot_exceed_max_score(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment(['max_score' => 10]);
        $studentId = $this->makeStudentId();
        $this->publish($id)->assertStatus(200);
        $this->assignByStudent($id, [$studentId])->assertStatus(200);
        $this->submit($id, $studentId)->assertStatus(200);

        $submissionId = $this->submissionId($id, $studentId);

        // BR008: score > max_score rejected.
        $this->postJson("/v1/edu/submission/grade/{$submissionId}", ['score' => 99])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_late_submission_blocked_unless_allowed(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();
        $studentId = $this->makeStudentId();
        $this->publish($id)->assertStatus(200);
        $this->assignByStudent($id, [$studentId])->assertStatus(200);

        // Move the due date into the past.
        DB::table('edu_assignments')->where('id', $id)->update(['due_date' => now()->subDay()]);

        // BR006: late submission rejected.
        $this->submit($id, $studentId)
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        DB::table('edu_assignments')->where('id', $id)->update(['allow_late_submission' => true]);

        $this->submit($id, $studentId)
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'late_submitted');
    }

    public function test_no_resubmit_unless_allowed(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();
        $studentId = $this->makeStudentId();
        $this->publish($id)->assertStatus(200);
        $this->assignByStudent($id, [$studentId])->assertStatus(200);

        $this->submit($id, $studentId)->assertStatus(200);

        // BR007: second submission rejected when not allowed.
        $this->submit($id, $studentId)
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_submitted_lists_all_students_who_have_submitted(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment(['max_score' => 10]);
        $submitted = $this->makeStudentId();
        $graded = $this->makeStudentId();
        $assignedOnly = $this->makeStudentId();
        $this->publish($id)->assertStatus(200);
        $this->assignByStudent($id, [$submitted, $graded, $assignedOnly])->assertStatus(200);
        $this->submit($id, $submitted)->assertStatus(200);
        $this->submit($id, $graded)->assertStatus(200);
        $this->postJson('/v1/edu/submission/grade/'.$this->submissionId($id, $graded), ['score' => 8])
            ->assertStatus(200);

        // Everyone who submitted appears — including the already-graded student;
        // only the merely-assigned, never-submitted student is excluded.
        $response = $this->getJson("/v1/edu/submission/submitted/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.items');

        $studentIds = collect($response->json('data.items'))->pluck('student_id')->all();
        $this->assertEqualsCanonicalizing([$submitted, $graded], $studentIds);
    }

    public function test_graded_lists_only_graded_submissions(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment(['max_score' => 10]);
        $graded = $this->makeStudentId();
        $submittedOnly = $this->makeStudentId();
        $this->publish($id)->assertStatus(200);
        $this->assignByStudent($id, [$graded, $submittedOnly])->assertStatus(200);
        $this->submit($id, $graded)->assertStatus(200);
        $this->submit($id, $submittedOnly)->assertStatus(200);

        $this->postJson('/v1/edu/submission/grade/'.$this->submissionId($id, $graded), ['score' => 8])
            ->assertStatus(200);

        // Only the graded submission appears — the still-submitted one is excluded.
        $this->getJson("/v1/edu/submission/graded/{$id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.student_id', $graded)
            ->assertJsonPath('data.items.0.status', 'graded');
    }

    public function test_submission_detail_includes_assignment(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment();
        $studentId = $this->makeStudentId();
        $this->publish($id)->assertStatus(200);
        $this->assignByStudent($id, [$studentId])->assertStatus(200);
        $this->submit($id, $studentId)->assertStatus(200);

        $submissionId = $this->submissionId($id, $studentId);

        $this->getJson("/v1/edu/submission/detail/{$submissionId}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $submissionId)
            ->assertJsonPath('data.assignment.id', $id);
    }

    public function test_update_grade_edits_an_already_graded_submission(): void
    {
        $this->actingAsAdmin();

        $id = $this->createAssignment(['max_score' => 10]);
        $studentId = $this->makeStudentId();
        $this->publish($id)->assertStatus(200);
        $this->assignByStudent($id, [$studentId])->assertStatus(200);
        $this->submit($id, $studentId)->assertStatus(200);

        $submissionId = $this->submissionId($id, $studentId);

        // Cannot update a grade before the submission has been graded.
        $this->putJson("/v1/edu/submission/update/{$submissionId}", ['score' => 7])
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->postJson("/v1/edu/submission/grade/{$submissionId}", ['score' => 6, 'comment' => 'Khá'])
            ->assertStatus(200);

        // Editing the existing grade keeps the graded status.
        $this->putJson("/v1/edu/submission/update/{$submissionId}", ['score' => 9, 'comment' => 'Phúc khảo'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.score', '9.00')
            ->assertJsonPath('data.comment', 'Phúc khảo')
            ->assertJsonPath('data.status', 'graded');

        // BR008 is still enforced on update.
        $this->putJson("/v1/edu/submission/update/{$submissionId}", ['score' => 99])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }
}
