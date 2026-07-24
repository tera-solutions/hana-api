<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class CertificateTemplateTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mẫu A',
            'preview_image' => 'https://cdn.example.test/templates/a.png',
            'placeholders' => ['student_name', 'course_name', 'issued_at'],
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/certificate-template/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/certificate-template/list')->assertJsonPath('code', 403);
    }

    public function test_create_and_list(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->postJson('/v1/edu/certificate-template/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Mẫu A')
            ->assertJsonPath('data.status', 'active');

        $this->getJson('/v1/edu/certificate-template/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->postJson('/v1/edu/certificate-template/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_update(): void
    {
        $this->actingAsAdmin($this->businessId);

        $id = $this->postJson('/v1/edu/certificate-template/create', $this->payload())->json('data.id');

        $this->putJson("/v1/edu/certificate-template/update/{$id}", ['name' => 'Mẫu A - v2'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Mẫu A - v2');
    }

    public function test_suspend_and_restore(): void
    {
        $this->actingAsAdmin($this->businessId);

        $id = $this->postJson('/v1/edu/certificate-template/create', $this->payload())->json('data.id');

        $this->postJson("/v1/edu/certificate-template/suspend/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        $this->postJson("/v1/edu/certificate-template/suspend/{$id}")->assertJsonPath('success', false);

        $this->postJson("/v1/edu/certificate-template/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_is_scoped_to_acting_business(): void
    {
        $bizA = $this->businessId;
        $bizB = $this->makeBusinessId();

        $this->actingAsAdmin($bizA);
        $this->postJson('/v1/edu/certificate-template/create', $this->payload())->assertStatus(200);

        $this->actingAsAdmin($bizB);
        $this->getJson('/v1/edu/certificate-template/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }
}
