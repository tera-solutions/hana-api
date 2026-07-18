<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    public function test_update_profile_saves_allowed_fields(): void
    {
        $user = $this->actingAsAdmin();

        $this->putJson('/api/auth/profile', [
            'full_name' => 'Nguyen Van B',
            'dob' => '1992-01-01',
            'gender' => 'female',
            'phone' => '0912345678',
            'avatar' => 'https://cdn.hana.edu.vn/a.png',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'Nguyen Van B')
            ->assertJsonPath('data.phone', '0912345678');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'full_name' => 'Nguyen Van B',
            'gender' => 'female',
            'phone' => '0912345678',
        ]);
    }

    public function test_update_profile_rejects_invalid_gender(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/auth/profile', ['gender' => 'unknown'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }

    public function test_change_password_succeeds_with_correct_current_password(): void
    {
        $user = $this->actingAsAdmin();

        $this->postJson('/api/auth/profile/change-password', [
            'current_password' => 'secret123',
            'new_password' => 'NewSecret@1',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewSecret@1', $user->fresh()->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = $this->actingAsAdmin();

        $this->postJson('/api/auth/profile/change-password', [
            'current_password' => 'wrong-password',
            'new_password' => 'NewSecret@1',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);

        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
    }

    public function test_change_password_requires_new_password_min_length(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/auth/profile/change-password', [
            'current_password' => 'secret123',
            'new_password' => 'short',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }
}
