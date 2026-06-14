<?php

namespace Tests\Feature;

use App\Modules\Education\Enrollment\Enums\EnrollmentStatus;
use App\Modules\Education\Enrollment\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class MetadataTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/auth/metadata')->assertJsonPath('code', 401);
    }

    public function test_returns_grouped_enum_catalog(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/auth/metadata')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'shared' => ['gender', 'guardian_relation'],
                    'system' => ['user_status', 'business_status', 'branch_status'],
                    'crm' => ['lead_status', 'parent_status'],
                    'education' => [
                        'student_status', 'class_status', 'class_learning_type',
                        'class_student_status', 'class_session_status', 'attendance_status', 'enrollment_status',
                    ],
                    'hr' => ['teacher_status', 'teacher_type'],
                    'finance' => ['invoice_status', 'payment_status', 'payment_method', 'debt_status', 'refund_status'],
                ],
            ]);
    }

    public function test_options_are_key_value_label_triples(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/auth/metadata')
            ->assertStatus(200)
            ->assertJsonPath('data.shared.gender.0', ['key' => 'MALE', 'value' => 'male', 'label' => 'Nam'])
            ->assertJsonPath('data.education.enrollment_status.0', ['key' => 'PENDING', 'value' => 'pending', 'label' => 'Chờ xác nhận']);
    }

    public function test_catalog_matches_model_constants(): void
    {
        $this->actingAsAdmin();

        $values = collect(
            $this->getJson('/api/auth/metadata')->json('data.education.enrollment_status')
        )->pluck('value')->all();

        // The enum is the single source of truth backing the model constants.
        $this->assertSame(EnrollmentStatus::values(), $values);
        $this->assertContains(Enrollment::STATUS_STUDYING, $values);
        $this->assertSame(EnrollmentStatus::Studying->value, Enrollment::STATUS_STUDYING);
    }
}
