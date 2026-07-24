<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class SubscriptionPackageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Starters',
            'code' => 'C_'.strtoupper(uniqid()),
            'duration_minutes' => 60,
            'price_per_lesson' => 100000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassId(): int
    {
        return DB::table('edu_classes')->insertGetId([
            'course_id' => $this->courseId,
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Gói tháng',
            'type' => 'month',
            'price' => 2400000,
            'sessions_included' => 12,
            'duration_days' => 30,
            'applicable_courses' => [],
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/subscription-package/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/fin/subscription-package/list')->assertJsonPath('code', 403);
    }

    public function test_create_and_list_with_summary(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->postJson('/v1/fin/subscription-package/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Gói tháng')
            ->assertJsonPath('data.status', 'active');

        $this->postJson('/v1/fin/subscription-package/create', $this->payload(['name' => 'Gói buổi', 'type' => 'session', 'price' => 94000]))
            ->assertStatus(200);

        $this->getJson('/v1/fin/subscription-package/list')
            ->assertStatus(200)
            ->assertJsonPath('data.summary.total', 2)
            ->assertJsonPath('data.summary.active', 2)
            ->assertJsonPath('data.summary.inactive', 0)
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->postJson('/v1/fin/subscription-package/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_price_required_unless_custom_type(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->postJson('/v1/fin/subscription-package/create', $this->payload(['type' => 'term', 'price' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('price');

        $this->postJson('/v1/fin/subscription-package/create', $this->payload(['type' => 'custom', 'price' => null]))
            ->assertStatus(200);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->postJson('/v1/fin/subscription-package/create', $this->payload())->assertStatus(200);

        $this->postJson('/v1/fin/subscription-package/create', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_toggle_status(): void
    {
        $this->actingAsAdmin($this->businessId);

        $id = $this->postJson('/v1/fin/subscription-package/create', $this->payload())->json('data.id');

        $this->postJson("/v1/fin/subscription-package/toggle/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        $this->postJson("/v1/fin/subscription-package/toggle/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_delete_blocked_when_in_use(): void
    {
        $this->actingAsAdmin($this->businessId);

        $id = $this->postJson('/v1/fin/subscription-package/create', $this->payload())->json('data.id');

        $studentId = DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('edu_enrollments')->insert([
            'code' => 'ENR_'.strtoupper(uniqid()),
            'business_id' => $this->businessId,
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'class_id' => $this->makeClassId(),
            'subscription_package_id' => $id,
            'status' => 'studying',
            'total_lessons' => 12,
            'price_per_lesson' => 200000,
            'enrolled_at' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson("/v1/fin/subscription-package/delete/{$id}")
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('fin_subscription_packages', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_delete_succeeds_when_not_in_use(): void
    {
        $this->actingAsAdmin($this->businessId);

        $id = $this->postJson('/v1/fin/subscription-package/create', $this->payload())->json('data.id');

        $this->postJson("/v1/fin/subscription-package/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('fin_subscription_packages', ['id' => $id]);
    }

    public function test_usages_returns_enrolled_students(): void
    {
        $this->actingAsAdmin($this->businessId);

        $id = $this->postJson('/v1/fin/subscription-package/create', $this->payload())->json('data.id');

        $studentId = DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Nguyễn Minh An',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('edu_enrollments')->insert([
            'code' => 'ENR_'.strtoupper(uniqid()),
            'business_id' => $this->businessId,
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'class_id' => $this->makeClassId(),
            'subscription_package_id' => $id,
            'status' => 'studying',
            'total_lessons' => 12,
            'price_per_lesson' => 200000,
            'enrolled_at' => '2026-07-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson("/v1/fin/subscription-package/usages/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.student_name', 'Nguyễn Minh An');
    }

    public function test_set_discount_rules_replaces_existing_rules(): void
    {
        $this->actingAsAdmin($this->businessId);

        $id = $this->postJson('/v1/fin/subscription-package/create', $this->payload())->json('data.id');

        $this->putJson("/v1/fin/subscription-package/discount-rules/{$id}", [
            'rules' => [
                ['type' => 'multi_term', 'value' => 10, 'condition' => '3 tháng', 'enabled' => true],
            ],
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.discount_rules.0.type', 'multi_term')
            ->assertJsonPath('data.discount_rules.0.value', '10.00');

        $this->assertSame(1, DB::table('fin_subscription_package_discount_rules')->where('package_id', $id)->count());

        // Replacing with a new set removes the old rule.
        $this->putJson("/v1/fin/subscription-package/discount-rules/{$id}", [
            'rules' => [
                ['type' => 'sibling', 'value' => 5, 'condition' => 'HV thứ 2', 'enabled' => true],
            ],
        ])->assertStatus(200);

        $this->assertSame(1, DB::table('fin_subscription_package_discount_rules')->where('package_id', $id)->count());
        $this->assertDatabaseHas('fin_subscription_package_discount_rules', ['package_id' => $id, 'type' => 'sibling']);
    }

    public function test_discount_rule_value_must_be_within_percent_range(): void
    {
        $this->actingAsAdmin($this->businessId);

        $id = $this->postJson('/v1/fin/subscription-package/create', $this->payload())->json('data.id');

        $this->putJson("/v1/fin/subscription-package/discount-rules/{$id}", [
            'rules' => [['type' => 'multi_term', 'value' => 150]],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rules.0.value');
    }

    public function test_is_scoped_to_acting_business(): void
    {
        $bizA = $this->businessId;
        $bizB = $this->makeBusinessId();

        $this->actingAsAdmin($bizA);
        $this->postJson('/v1/fin/subscription-package/create', $this->payload())->assertStatus(200);

        $this->actingAsAdmin($bizB);
        $this->getJson('/v1/fin/subscription-package/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }
}
