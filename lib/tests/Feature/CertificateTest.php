<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $courseId;

    private int $classId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = $this->makeCourseId();
        $this->classId = $this->makeClassId($this->courseId);
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Kids English',
            'code' => 'C_'.strtoupper(uniqid()),
            'duration_minutes' => 60,
            'price_per_lesson' => 100000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassId(int $courseId): int
    {
        return DB::table('edu_classes')->insertGetId([
            'course_id' => $courseId,
            'business_id' => $this->businessId,
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'Class '.uniqid(),
            'learning_type' => 'scheduled',
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
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeEnrollment(int $classId, int $courseId, int $studentId, string $status = 'studying'): void
    {
        // business_id matters here: Enrollment's BelongsToBusiness global
        // scope is active for requests running under an acting tenant user
        // (e.g. eligibleStudentsByCourse()'s Enrollment query).
        DB::table('edu_enrollments')->insert([
            'code' => 'ENR_'.strtoupper(uniqid()),
            'business_id' => $this->businessId,
            'student_id' => $studentId,
            'course_id' => $courseId,
            'class_id' => $classId,
            'status' => $status,
            'total_lessons' => 24,
            'price_per_lesson' => 100000,
            'enrolled_at' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeFinalGrade(int $classId, int $studentId, float $score = 9.0): void
    {
        DB::table('edu_grades')->insert([
            'business_id' => $this->businessId,
            'student_id' => $studentId,
            'class_id' => $classId,
            'score' => $score,
            'type' => 'final',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeTemplateId(): int
    {
        return DB::table('edu_certificate_templates')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Mẫu A',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/certificate/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/certificate/list')->assertJsonPath('code', 403);
    }

    public function test_issue_requires_final_grade(): void
    {
        $this->actingAsAdmin();
        $studentId = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $studentId);

        $this->postJson("/v1/edu/certificate/{$this->classId}/issue", ['student_id' => $studentId])
            ->assertJsonPath('success', false);
    }

    public function test_issue_creates_certificate_and_verify_works(): void
    {
        $this->actingAsAdmin();
        $studentId = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $studentId);
        $this->makeFinalGrade($this->classId, $studentId, 9.5);

        $response = $this->postJson("/v1/edu/certificate/{$this->classId}/issue", ['student_id' => $studentId])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'issued');

        $token = DB::table('edu_certificates')->where('id', $response->json('data.id'))->value('verify_token');

        $studentName = DB::table('edu_students')->where('id', $studentId)->value('name');

        $this->getJson("/v1/edu/certificate/verify/{$token}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'issued')
            ->assertJsonPath('data.final_score', '9.50')
            ->assertJsonPath('data.student_name', $studentName);
    }

    public function test_eligibility_returns_student_name_and_code(): void
    {
        $this->actingAsAdmin();
        $studentId = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $studentId);
        $this->makeFinalGrade($this->classId, $studentId, 8.0);

        $studentName = DB::table('edu_students')->where('id', $studentId)->value('name');
        $studentCode = DB::table('edu_students')->where('id', $studentId)->value('code');

        $this->getJson("/v1/edu/certificate/{$this->classId}/eligibility")
            ->assertStatus(200)
            ->assertJsonPath('data.0.student.full_name', $studentName)
            ->assertJsonPath('data.0.student.student_code', $studentCode);
    }

    public function test_cannot_issue_twice_for_the_same_class(): void
    {
        $this->actingAsAdmin();
        $studentId = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $studentId);
        $this->makeFinalGrade($this->classId, $studentId);

        $this->postJson("/v1/edu/certificate/{$this->classId}/issue", ['student_id' => $studentId])->assertStatus(200);

        $this->postJson("/v1/edu/certificate/{$this->classId}/issue", ['student_id' => $studentId])
            ->assertJsonPath('success', false);
    }

    public function test_revoke_certificate(): void
    {
        $this->actingAsAdmin();
        $studentId = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $studentId);
        $this->makeFinalGrade($this->classId, $studentId);

        $id = $this->postJson("/v1/edu/certificate/{$this->classId}/issue", ['student_id' => $studentId])
            ->json('data.id');

        $this->postJson("/v1/edu/certificate/revoke/{$id}", ['reason' => 'Gian lận thi cử'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'revoked');

        $this->assertDatabaseHas('edu_certificates', ['id' => $id, 'status' => 'revoked']);
    }

    public function test_list_returns_summary_and_paginated_items(): void
    {
        $this->actingAsAdmin();
        $studentId = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $studentId);
        $this->makeFinalGrade($this->classId, $studentId);
        $this->postJson("/v1/edu/certificate/{$this->classId}/issue", ['student_id' => $studentId]);
        $this->makeTemplateId();

        $this->getJson('/v1/edu/certificate/list')
            ->assertStatus(200)
            ->assertJsonPath('data.summary.issued', 1)
            ->assertJsonPath('data.summary.templates', 1)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.status', 'issued');
    }

    public function test_eligible_students_filters_by_completion_threshold(): void
    {
        $this->actingAsAdmin();

        $fullAttendanceStudent = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $fullAttendanceStudent);

        $noAttendanceStudent = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $noAttendanceStudent);

        $sessionId = DB::table('edu_sessions')->insertGetId([
            'business_id' => $this->businessId,
            'class_id' => $this->classId,
            'session_no' => 1,
            'code' => 'SES_'.strtoupper(uniqid()),
            'name' => 'Session 1',
            'session_date' => now()->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('edu_attendances')->insert([
            'session_id' => $sessionId,
            'student_id' => $fullAttendanceStudent,
            'status' => 'present',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('edu_attendances')->insert([
            'session_id' => $sessionId,
            'student_id' => $noAttendanceStudent,
            'status' => 'absent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/v1/edu/certificate/eligible-students?course_id={$this->courseId}&threshold=100")
            ->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($fullAttendanceStudent, $ids);
        $this->assertNotContains($noAttendanceStudent, $ids);
    }

    public function test_issue_bulk_issues_to_multiple_students_and_skips_duplicates(): void
    {
        $this->actingAsAdmin();
        $templateId = $this->makeTemplateId();
        $student1 = $this->makeStudentId();
        $student2 = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $student1);
        $this->makeEnrollment($this->classId, $this->courseId, $student2);

        $this->postJson('/v1/edu/certificate/issue-bulk', [
            'course_id' => $this->courseId,
            'student_ids' => [$student1, $student2],
            'template_id' => $templateId,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.issued_count', 2);

        $this->assertSame(2, DB::table('edu_certificates')->where('course_id', $this->courseId)->count());

        // Re-issuing to the same student is skipped, not duplicated.
        $this->postJson('/v1/edu/certificate/issue-bulk', [
            'course_id' => $this->courseId,
            'student_ids' => [$student1],
            'template_id' => $templateId,
        ])->assertJsonPath('data.issued_count', 0);

        $this->assertSame(2, DB::table('edu_certificates')->where('course_id', $this->courseId)->count());
    }

    public function test_issue_bulk_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/certificate/issue-bulk', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['course_id', 'student_ids', 'template_id']);
    }

    public function test_download_returns_a_pdf(): void
    {
        $this->actingAsAdmin();
        $studentId = $this->makeStudentId();
        $this->makeEnrollment($this->classId, $this->courseId, $studentId);
        $this->makeFinalGrade($this->classId, $studentId);

        $id = $this->postJson("/v1/edu/certificate/{$this->classId}/issue", ['student_id' => $studentId])
            ->json('data.id');

        $response = $this->get("/v1/edu/certificate/download/{$id}");

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }
}
