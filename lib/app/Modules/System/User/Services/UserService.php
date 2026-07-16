<?php

namespace App\Modules\System\User\Services;

use App\Models\User;
use App\Modules\System\User\Events\UserCreated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Package\Database\Concerns\HandlesEntityQueries;
use Package\Tenancy\TenantContext;

class UserService
{
    use HandlesEntityQueries;

    /**
     * Confine a User query to the acting business. The User model deliberately
     * carries no global BusinessScope (it would recurse through Passport's
     * token→user resolution), so tenant isolation for users is enforced here.
     */
    private function scopeToBusiness(Builder $query): Builder
    {
        $businessId = TenantContext::businessId();

        if ($businessId !== null) {
            $query->where('business_id', $businessId);
        }

        return $query;
    }

    /**
     * Paginated, searchable, filterable, sortable list.
     */
    public function paginate(array $params = [])
    {
        $query = $this->scopeToBusiness(User::query());

        // Search: code (user id), full_name, email, phone
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filters
        foreach (['business_id', 'branch_id', 'role_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['created_from'])) {
            $query->whereDate('created_at', '>=', $params['created_from']);
        }
        if (! empty($params['created_to'])) {
            $query->whereDate('created_at', '<=', $params['created_to']);
        }

        $this->applySort($query, $params, ['code', 'full_name', 'email', 'created_at', 'status']);

        return $query->with(['business', 'branch', 'role'])->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return $this->scopeToBusiness(User::with(['business', 'branch', 'role']))->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            unset($data['password_confirmation']);

            $data['password'] = Hash::make($data['password']);
            $data['avatar'] = $data['avatar'] ?? '';
            $data['status'] = $data['status'] ?? 'active';
            $data['is_active'] = $data['status'] === 'active';
            $data['is_admin'] = $data['is_admin'] ?? false;

            // A new user always belongs to the acting business; a client-supplied
            // business_id cannot place a user in another tenant.
            if (($businessId = TenantContext::businessId()) !== null) {
                $data['business_id'] = $businessId;
            }
            // Temporary code; replaced with the USR###### code once the id is known.
            $data['code'] = 'TMP_'.Str::upper(Str::random(10));

            $user = User::create($data);

            $user->code = 'USR'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);
            $user->saveQuietly();

            event(new UserCreated($user));

            return $user->load(['business', 'branch', 'role']);
        });
    }

    public function update($id, array $data)
    {
        $model = $this->find($id);

        // ID, Username and Code are immutable.
        unset($data['id'], $data['username'], $data['code'], $data['password']);

        if (array_key_exists('status', $data)) {
            $data['is_active'] = $data['status'] === 'active';
        }

        $model->update($data);

        return $model->load(['business', 'branch', 'role']);
    }

    /**
     * Soft delete (deactivate) the user, blocked when constraints exist.
     *
     * @throws \RuntimeException
     */
    public function delete($id)
    {
        $model = $this->find($id);

        if ($reason = $this->deletionBlockReason($model)) {
            throw new \RuntimeException($reason);
        }

        // Spec: deactivate rather than hard delete.
        $model->status = 'inactive';
        $model->is_active = false;
        $model->save();

        return $model->delete();
    }

    /**
     * Returns a human reason when the user must not be deleted, or null.
     */
    public function deletionBlockReason(User $user): ?string
    {
        // Last remaining active super admin of this business.
        if ($user->is_admin) {
            $otherAdmins = $this->scopeToBusiness(
                User::where('is_admin', true)
                    ->where('id', '!=', $user->id)
                    ->whereNull('deleted_at')
            )->count();
            if ($otherAdmins === 0) {
                return 'Không thể xóa tài khoản Super Admin cuối cùng.';
            }
        }

        // [table => foreign key column] referencing this user.
        $constraints = [
            'sys_business' => 'manager_id',   // managing a business
            'sys_branches' => 'manager_id',   // managing a branch
            'hr_teachers' => 'user_id',       // linked teacher record
        ];

        if ($this->hasLinkedData($user->id, $constraints)) {
            return 'Không thể xóa người dùng vì đang tồn tại dữ liệu liên quan.';
        }

        return null;
    }

    public function activate($id)
    {
        return $this->setState($id, 'active', true);
    }

    public function deactivate($id)
    {
        return $this->setState($id, 'inactive', false);
    }

    public function unlock($id)
    {
        return $this->setState($id, 'active', true);
    }

    public function resetPassword($id): array
    {
        $user = $this->find($id);

        $newPassword = $this->generatePassword();
        $user->password = Hash::make($newPassword);
        $user->save();

        // No mail infrastructure here; return the generated password to the caller.
        return ['user' => $user, 'password' => $newPassword];
    }

    private function setState($id, string $status, bool $isActive): User
    {
        $user = $this->find($id);
        $user->status = $status;
        $user->is_active = $isActive;
        $user->save();

        return $user;
    }

    private function generatePassword(): string
    {
        // Meets the policy: upper, lower, digit, special, >= 8 chars.
        return 'Aa1'.Str::password(9, symbols: true);
    }
}
