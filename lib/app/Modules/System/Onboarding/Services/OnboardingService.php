<?php

namespace App\Modules\System\Onboarding\Services;

use App\Models\Role;
use App\Models\User;
use App\Modules\Finance\Wallet\Models\Wallet;
use App\Modules\Finance\Wallet\Services\WalletService;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use App\Modules\System\Package\Models\Package;
use App\Modules\System\Subscription\Models\Subscription;
use App\Modules\System\Subscription\Services\SubscriptionService;
use App\Modules\System\User\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Self-service onboarding: a new center registers itself from the Teacher app,
 * producing an isolated tenant (Business) with an owner Admin account and a
 * trial subscription. Runs unauthenticated, so tenant context is null and every
 * business_id is set explicitly here.
 */
class OnboardingService
{
    /** Code of the internal, non-purchasable trial package that backs new signups. */
    private const TRIAL_PACKAGE_CODE = 'PKG-TRIAL';

    /** Trial length granted on signup, in days. */
    private const TRIAL_DAYS = 14;

    public function __construct(
        private readonly UserService $users,
        private readonly SubscriptionService $subscriptions,
        private readonly WalletService $wallets,
    ) {}

    /**
     * @return array{business: Business, user: User}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $business = Business::create([
                'name' => $data['school'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'status' => 'active',
            ]);

            $role = $this->createOwnerRole($business->id);
            $branch = $this->createDefaultBranch($business->id, $data);

            $user = $this->users->create([
                'full_name' => $data['full_name'],
                'username' => $data['email'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
                'avatar' => $data['avatar'] ?? null,
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'role_id' => $role->id,
                'is_admin' => true,
                'status' => 'active',
            ]);

            $business->update(['manager_id' => $user->id]);

            $this->createOwnerTeacher($business->id, $user->id, $branch->id, $data);
            $this->wallets->createForOwner($business->id, Wallet::OWNER_TEACHER, $user->id);

            $this->startTrial($business->id);

            return ['business' => $business->refresh(), 'user' => $user];
        });
    }

    private function createOwnerRole(int $businessId): Role
    {
        return Role::create([
            'business_id' => $businessId,
            'title' => 'Quản trị viên',
            'code' => 'ADMIN#'.$businessId,
            'type' => 'system',
            'guard_name' => 'api',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    private function createOwnerTeacher(int $businessId, int $userId, int $branchId, array $data): Teacher
    {
        $teacher = Teacher::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'branch_id' => $branchId,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'gender' => $data['gender'] ?? null,
            'dob' => $data['dob'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'teacher_type' => 'teacher',
            'status' => Teacher::STATUS_ACTIVE,
            'joined_at' => now()->toDateString(),
            'note' => $data['bio'] ?? null,
            'code' => 'TMP_'.strtoupper(uniqid()),
        ]);

        $teacher->code = 'GV'.str_pad((string) $teacher->id, 6, '0', STR_PAD_LEFT);
        $teacher->saveQuietly();

        return $teacher;
    }

    /**
     * A tenant needs at least one branch before it can create students/rooms/
     * teachers through their own validated endpoints (all require branch_id).
     * Self-signup only collects a single-location school, so this is it — the
     * owner can add more branches later via the branch management screen.
     */
    private function createDefaultBranch(int $businessId, array $data): Branch
    {
        return Branch::create([
            'business_id' => $businessId,
            'name' => $data['school'],
            'code' => 'CN-'.Str::upper(Str::random(6)),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => 'Chưa cập nhật',
            'is_main_branch' => true,
        ]);
    }

    private function startTrial(int $businessId): Subscription
    {
        $package = $this->trialPackage();

        $subscription = Subscription::create([
            'business_id' => $businessId,
            'package_id' => $package->id,
            'price' => 0,
            'billing_cycle' => 'month',
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now()->toDateString(),
            'expires_at' => now()->addDays(self::TRIAL_DAYS)->toDateString(),
        ]);

        $this->subscriptions->recordInvoice($subscription, $package, $businessId, 'month', null);

        return $subscription;
    }

    /**
     * The trial package is an internal artifact (hidden from the purchasable
     * package listing via is_active=false); created on demand so signup works
     * even before the seeder has run.
     */
    private function trialPackage(): Package
    {
        return Package::firstOrCreate(
            ['package_code' => self::TRIAL_PACKAGE_CODE],
            [
                'name' => 'Gói Dùng thử',
                'description' => 'Trải nghiệm miễn phí trong '.self::TRIAL_DAYS.' ngày.',
                'price' => 0,
                'billing_cycle' => 'month',
                'features' => ['Quản lý lớp học', 'Điểm danh học viên', 'Giáo án cơ bản'],
                'limits' => ['students' => 20, 'classes' => 3, 'teachers' => 2, 'branches' => 1, 'parents' => 20],
                'badge' => 'Dùng thử',
                'is_active' => false,
                'sort_order' => 0,
            ]
        );
    }
}
