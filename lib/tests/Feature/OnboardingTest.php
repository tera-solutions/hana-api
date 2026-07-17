<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use App\Modules\System\Subscription\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $uniq = uniqid();

        return array_merge([
            'full_name' => 'Nguyen Van A',
            'email' => "owner_{$uniq}@hana.edu.vn",
            'phone' => '09'.str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'Abc@1234',
            'password_confirmation' => 'Abc@1234',
            'gender' => 'male',
            'dob' => '1995-05-20',
            'school' => 'Hana English '.$uniq,
            'position' => 'Giáo viên IELTS',
            'experience' => 5,
            'subject' => 'Tiếng Anh',
            'bio' => 'Giáo viên với 5 năm kinh nghiệm.',
            'app_id' => 2,
        ], $overrides);
    }

    public function test_registration_provisions_a_full_tenant(): void
    {
        $payload = $this->payload();

        $response = $this->postJson('/api/auth/register-school', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.business.name', $payload['school'])
            ->assertJsonPath('data.user.email', $payload['email'])
            ->assertJsonPath('data.is_verify', 1);

        $business = Business::where('email', $payload['email'])->firstOrFail();
        $user = User::where('email', $payload['email'])->firstOrFail();

        // Owner is an active admin of the new (and only) business.
        $this->assertTrue((bool) $user->is_admin);
        $this->assertTrue((bool) $user->is_active);
        $this->assertSame($business->id, $user->business_id);
        $this->assertSame($user->id, $business->manager_id);
        $this->assertTrue(Hash::check($payload['password'], $user->password));
        $this->assertStringStartsWith('USR', $user->code);

        // A default admin role was created for the tenant and linked to the owner.
        $this->assertNotNull($user->role_id);
        $this->assertDatabaseHas('sys_roles', [
            'id' => $user->role_id,
            'business_id' => $business->id,
            'code' => 'ADMIN#'.$business->id,
        ]);

        // Owner has a matching teacher profile (Teacher app identity).
        $teacher = Teacher::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($business->id, $teacher->business_id);
        $this->assertStringStartsWith('GV', $teacher->code);
        $this->assertSame($payload['bio'], $teacher->note);

        // A default branch exists so students/rooms/teachers can be created
        // right away (their create endpoints all require branch_id).
        $branch = Branch::where('business_id', $business->id)->firstOrFail();
        $this->assertTrue((bool) $branch->is_main_branch);

        // A 14-day trial subscription is active.
        $subscription = Subscription::where('business_id', $business->id)->firstOrFail();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame(
            now()->addDays(14)->toDateString(),
            $subscription->expires_at->toDateString(),
        );
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $first = $this->payload();
        $this->postJson('/api/auth/register-school', $first)->assertStatus(200);

        $this->postJson('/api/auth/register-school', $this->payload(['email' => $first['email']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->postJson('/api/auth/register-school', $this->payload([
            'password_confirmation' => 'Different@1',
        ]))->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_school_name_is_required(): void
    {
        $this->postJson('/api/auth/register-school', $this->payload(['school' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['school']);
    }

    public function test_a_failed_signup_creates_nothing(): void
    {
        // Missing school triggers validation failure before the transaction runs.
        $this->postJson('/api/auth/register-school', $this->payload([
            'school' => '',
            'email' => 'atomic@hana.edu.vn',
        ]))->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => 'atomic@hana.edu.vn']);
        $this->assertDatabaseMissing('sys_business', ['email' => 'atomic@hana.edu.vn']);
    }
}
