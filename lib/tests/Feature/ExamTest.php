<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $courseId;

    private int $level1;

    private int $level2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId();
        $this->courseId = $this->makeCourseId();
        $this->level1 = $this->makeLevel('STARTER', 1);
        $this->level2 = $this->makeLevel('MOVER', 2);
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

    private function makeLevel(string $code, int $order): int
    {
        return DB::table('edu_levels')->insertGetId([
            'level_code' => $code.'_'.strtoupper(uniqid()),
            'level_name' => $code,
            'course_id' => $this->courseId,
            'level_order' => $order,
            'status' => 'active',
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

    private function makeClassId(): int
    {
        return DB::table('edu_classes')->insertGetId([
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'Exam Class',
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'learning_type' => 'flexible',
            'status' => 'upcoming',
            'start_date' => now()->addDays(7)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeRoomId(): int
    {
        return DB::table('edu_rooms')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'room_name' => 'Room '.uniqid(),
            'room_code' => 'RM_'.strtoupper(uniqid()),
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

    private function seedStudentLevel(int $studentId, int $levelId): int
    {
        return DB::table('edu_student_levels')->insertGetId([
            'business_id' => $this->businessId,
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'level_id' => $levelId,
            'assigned_at' => now(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function examPayload(array $overrides = []): array
    {
        return array_merge([
            'exam_name' => 'Final Test - Starter',
            'exam_type' => 'final',
            'course_id' => $this->courseId,
            'level_id' => $this->level1,
            'duration' => 60,
            'total_score' => 100,
            'passing_score' => 70,
        ], $overrides);
    }

    private function createExam(array $overrides = []): int
    {
        return $this->postJson('/v1/edu/exam/create', $this->examPayload($overrides))->json('data.id');
    }

    private function createSession(int $examId, array $overrides = []): TestResponse
    {
        return $this->postJson('/v1/edu/exam-session/create', array_merge([
            'exam_id' => $examId,
            'exam_date' => now()->addDays(10)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:30',
        ], $overrides));
    }

    private function registrationId(int $sessionId, int $studentId): int
    {
        return (int) DB::table('edu_exam_registrations')
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->value('id');
    }

    // ── Exam bank ────────────────────────────────────────────────────────────────

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/exam/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->postJson('/v1/edu/exam/create', [])->assertJsonPath('code', 403);
    }

    public function test_create_starts_as_draft_with_generated_code(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/exam/create', $this->examPayload())
            ->assertStatus(200)
            ->assertJsonPath('data.exam_code', 'EXM000001')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_create_validation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/exam/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['exam_name', 'exam_type', 'duration', 'total_score', 'passing_score']);

        // passing_score must not exceed total_score.
        $this->postJson('/v1/edu/exam/create', $this->examPayload(['passing_score' => 120]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['passing_score']);
    }

    private function addQuestion(int $examId): void
    {
        $this->postJson("/v1/edu/exam/question/create/{$examId}", [
            'skill' => 'reading',
            'question_type' => 'single_choice',
            'content' => 'Q1?',
            'answer_key' => ['A'],
            'score' => 2,
        ])->assertStatus(200);
    }

    public function test_clone_makes_a_standalone_copy_at_version_one(): void
    {
        $this->actingAsAdmin();

        $id = $this->createExam();
        $this->addQuestion($id);

        // Clone (exam.md §IV) is an independent copy: fresh lineage, version 1.
        $this->postJson("/v1/edu/exam/clone/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.exam_code', 'EXM000002')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.root_exam_id', null)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(1, 'data.questions');
    }

    public function test_update_edits_a_draft_in_place(): void
    {
        $this->actingAsAdmin();

        $id = $this->createExam();

        // A draft is freely editable: same row, same version.
        $this->putJson("/v1/edu/exam/update/{$id}", $this->examPayload(['exam_name' => 'Renamed']))
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.exam_name', 'Renamed');

        $this->getJson("/v1/edu/exam/version/list/{$id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_update_forks_a_new_version_when_in_use(): void
    {
        $this->actingAsAdmin();

        $id = $this->createExam();
        $this->addQuestion($id);

        // Scheduling a session puts the exam in use (exam.md §IV).
        $this->createSession($id)->assertStatus(200);

        // Editing an in-use exam forks a new draft version sharing the lineage root; the
        // questions come along and the original stays untouched.
        $new = $this->putJson("/v1/edu/exam/update/{$id}", $this->examPayload(['exam_name' => 'Revised']))
            ->assertStatus(200)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.root_exam_id', $id)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.exam_name', 'Revised')
            ->assertJsonCount(1, 'data.questions')
            ->json('data.id');

        $this->assertNotSame($id, $new);

        // The original is immutable: unchanged name, still version 1.
        $this->getJson("/v1/edu/exam/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.exam_name', 'Final Test - Starter');

        // The lineage now lists both versions, oldest first.
        $this->getJson("/v1/edu/exam/version/list/{$id}")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.version', 1)
            ->assertJsonPath('data.1.version', 2);
    }

    public function test_version_detail_returns_the_exam_version(): void
    {
        $this->actingAsAdmin();

        $id = $this->createExam();
        $this->addQuestion($id);

        // Version detail (exam.md §IV) is read-only: the exam version with its questions.
        $this->getJson("/v1/edu/exam/version/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.version', 1)
            ->assertJsonCount(1, 'data.questions');
    }

    public function test_version_list_returns_the_lineage(): void
    {
        $this->actingAsAdmin();

        $id = $this->createExam();

        // The lineage lists the exam itself (a standalone exam is its own lineage root).
        $this->getJson("/v1/edu/exam/version/list/{$id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.version', 1);
    }

    public function test_question_crud(): void
    {
        $this->actingAsAdmin();

        $id = $this->createExam();

        $questionId = $this->postJson("/v1/edu/exam/question/create/{$id}", [
            'skill' => 'grammar',
            'question_type' => 'fill_blank',
            'content' => 'He ___ to school.',
            'answer_key' => ['goes'],
            'score' => 5,
        ])->assertStatus(200)->json('data.id');

        $this->putJson("/v1/edu/exam/question/update/{$questionId}", ['score' => 8])
            ->assertStatus(200)
            ->assertJsonPath('data.score', '8.00');

        $this->deleteJson("/v1/edu/exam/question/delete/{$questionId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_delete_soft_deletes_exam(): void
    {
        $this->actingAsAdmin();

        $id = $this->createExam();

        $this->deleteJson("/v1/edu/exam/delete/{$id}")->assertStatus(200);
        $this->assertSoftDeleted('edu_exams', ['id' => $id]);
    }

    // ── Sessions & scheduling ────────────────────────────────────────────────────

    public function test_create_session(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();

        $this->createSession($examId, ['room_id' => $this->makeRoomId(), 'teacher_id' => $this->makeTeacherId()])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'scheduled');
    }

    public function test_session_rejects_room_conflict(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();
        $roomId = $this->makeRoomId();
        $date = now()->addDays(10)->toDateString();

        $this->createSession($examId, ['room_id' => $roomId, 'exam_date' => $date, 'start_time' => '09:00', 'end_time' => '10:30'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // BR001: overlapping booking of the same room is rejected.
        $this->createSession($examId, ['room_id' => $roomId, 'exam_date' => $date, 'start_time' => '10:00', 'end_time' => '11:00'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_session_rejects_invigilator_conflict(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();
        $teacherId = $this->makeTeacherId();
        $date = now()->addDays(10)->toDateString();

        $this->createSession($examId, ['teacher_id' => $teacherId, 'exam_date' => $date, 'start_time' => '09:00', 'end_time' => '10:30'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // BR002: overlapping booking of the same invigilator is rejected.
        $this->createSession($examId, ['teacher_id' => $teacherId, 'exam_date' => $date, 'start_time' => '10:00', 'end_time' => '11:00'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    // ── Registration ─────────────────────────────────────────────────────────────

    public function test_register_by_class_targets_active_students(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();
        $sessionId = $this->createSession($examId)->json('data.id');

        $classId = $this->makeClassId();
        $this->enroll($classId, $this->makeStudentId());
        $this->enroll($classId, $this->makeStudentId());
        $this->enroll($classId, $this->makeStudentId(), 'dropped');

        $this->postJson("/v1/edu/exam-session/register/class/{$sessionId}", ['class_room_id' => $classId])
            ->assertStatus(200)
            ->assertJsonPath('data.registered', 2);

        // BR004: re-registering the same class is idempotent.
        $this->postJson("/v1/edu/exam-session/register/class/{$sessionId}", ['class_room_id' => $classId])
            ->assertStatus(200)
            ->assertJsonPath('data.registered', 0);
    }

    public function test_register_blocked_when_session_closed(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();
        $sessionId = $this->createSession($examId)->json('data.id');

        DB::table('edu_exam_sessions')->where('id', $sessionId)->update(['status' => 'closed']);

        // BR005: cannot register once the sitting is closed.
        $this->postJson("/v1/edu/exam-session/register/student/{$sessionId}", ['student_ids' => [$this->makeStudentId()]])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    // ── Grading, publishing, promotion ───────────────────────────────────────────

    public function test_grade_classifies_and_marks_passed(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();
        $sessionId = $this->createSession($examId)->json('data.id');
        $studentId = $this->makeStudentId();
        $this->postJson("/v1/edu/exam-session/register/student/{$sessionId}", ['student_ids' => [$studentId]]);

        $registrationId = $this->registrationId($sessionId, $studentId);

        // Total 85/100 -> good, passed.
        $this->postJson("/v1/edu/exam-result/grade/{$registrationId}", [
            'listening_score' => 20,
            'speaking_score' => 15,
            'reading_score' => 20,
            'writing_score' => 15,
            'grammar_score' => 10,
            'vocabulary_score' => 5,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.total_score', '85.00')
            ->assertJsonPath('data.grade', 'good')
            ->assertJsonPath('data.passed', true);

        $this->assertDatabaseHas('edu_exam_registrations', ['id' => $registrationId, 'status' => 'graded']);
    }

    public function test_grade_rejects_total_over_max(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();
        $sessionId = $this->createSession($examId)->json('data.id');
        $studentId = $this->makeStudentId();
        $this->postJson("/v1/edu/exam-session/register/student/{$sessionId}", ['student_ids' => [$studentId]]);

        $registrationId = $this->registrationId($sessionId, $studentId);

        // BR006: total over the exam max is rejected.
        $this->postJson("/v1/edu/exam-result/grade/{$registrationId}", [
            'listening_score' => 60,
            'speaking_score' => 60,
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_publish_stamps_result(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();
        $sessionId = $this->createSession($examId)->json('data.id');
        $studentId = $this->makeStudentId();
        $this->postJson("/v1/edu/exam-session/register/student/{$sessionId}", ['student_ids' => [$studentId]]);
        $registrationId = $this->registrationId($sessionId, $studentId);
        $this->postJson("/v1/edu/exam-result/grade/{$registrationId}", ['reading_score' => 80]);

        $this->postJson("/v1/edu/exam-result/publish/{$registrationId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('edu_exam_registrations', ['id' => $registrationId, 'status' => 'published']);
        $this->assertNotNull(DB::table('edu_exam_results')->where('exam_session_id', $sessionId)->where('student_id', $studentId)->value('published_at'));
    }

    public function test_promote_requires_passing_result(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();
        $sessionId = $this->createSession($examId)->json('data.id');
        $studentId = $this->makeStudentId();
        $this->seedStudentLevel($studentId, $this->level1);
        $this->postJson("/v1/edu/exam-session/register/student/{$sessionId}", ['student_ids' => [$studentId]]);
        $registrationId = $this->registrationId($sessionId, $studentId);

        // BR008: a failing result cannot promote.
        $this->postJson("/v1/edu/exam-result/grade/{$registrationId}", ['reading_score' => 50]);
        $this->postJson("/v1/edu/exam-result/promote/{$registrationId}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_promote_moves_level_and_logs_exam_result(): void
    {
        $this->actingAsAdmin();

        $examId = $this->createExam();
        $sessionId = $this->createSession($examId)->json('data.id');
        $studentId = $this->makeStudentId();
        $studentLevelId = $this->seedStudentLevel($studentId, $this->level1);
        $this->postJson("/v1/edu/exam-session/register/student/{$sessionId}", ['student_ids' => [$studentId]]);
        $registrationId = $this->registrationId($sessionId, $studentId);
        $this->postJson("/v1/edu/exam-result/grade/{$registrationId}", ['reading_score' => 80]);

        $resultId = DB::table('edu_exam_results')->where('exam_session_id', $sessionId)->where('student_id', $studentId)->value('id');

        $this->postJson("/v1/edu/exam-result/promote/{$registrationId}")
            ->assertStatus(200)
            ->assertJsonPath('data.level_id', $this->level2);

        $this->assertDatabaseHas('edu_student_level_histories', [
            'student_level_id' => $studentLevelId,
            'action' => 'promote',
            'to_level_id' => $this->level2,
            'exam_result_id' => $resultId,
        ]);
    }

    // ── TeacherScope ─────────────────────────────────────────────────────────

    /** A non-admin, hr_teachers-linked user — TeacherScope::current() applies to it. */
    private function actingAsTeacher(array $permissions = []): int
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, $permissions);
        $user = $this->makeUser(false, $roleId, $this->businessId);

        $teacherId = DB::table('hr_teachers')->insertGetId([
            'user_id' => $user->id,
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Teacher '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsApi($user);

        return $teacherId;
    }

    public function test_teacher_can_manage_own_exam_with_no_linked_class(): void
    {
        $this->actingAsTeacher(['exam.list', 'exam.view', 'exam.create', 'exam.update', 'exam.delete']);

        // No class links this teacher to the course — only authorship does.
        $examId = $this->postJson('/v1/edu/exam/create', $this->examPayload())->json('data.id');

        $this->getJson("/v1/edu/exam/detail/{$examId}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $examId);

        $this->putJson("/v1/edu/exam/update/{$examId}", ['exam_name' => 'Renamed by author'])
            ->assertStatus(200)
            ->assertJsonPath('data.exam_name', 'Renamed by author');

        $this->deleteJson("/v1/edu/exam/delete/{$examId}")->assertStatus(200);
    }

    public function test_teacher_cannot_access_an_unrelated_exam_they_did_not_create(): void
    {
        $this->actingAsAdmin();
        $examId = $this->createExam();

        // Different teacher: no class on the exam's course, and not the author.
        $this->actingAsTeacher(['exam.list', 'exam.view', 'exam.update', 'exam.delete']);

        $this->getJson("/v1/edu/exam/detail/{$examId}")->assertJsonPath('code', 403);
        $this->putJson("/v1/edu/exam/update/{$examId}", ['exam_name' => 'Hijacked'])->assertJsonPath('code', 403);
        $this->deleteJson("/v1/edu/exam/delete/{$examId}")->assertJsonPath('code', 403);
    }

    public function test_teacher_with_class_on_the_course_can_access_a_colleagues_exam(): void
    {
        $this->actingAsAdmin();
        $examId = $this->createExam();

        $teacherId = $this->actingAsTeacher(['exam.list', 'exam.view', 'exam.update']);
        DB::table('edu_classes')->insert([
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'Owned Class',
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'teacher_id' => $teacherId,
            'learning_type' => 'flexible',
            'status' => 'upcoming',
            'start_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson("/v1/edu/exam/detail/{$examId}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $examId);
    }
}
