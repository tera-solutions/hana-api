<?php

namespace App\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Package\Database\Concerns\HasAuditFields;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasAuditFields;
    use HasRoles;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $appends = ['avatar_url', 'is_superadmin'];

    public function getBearerToken()
    {
        if (! request()->header('Authorization')) {
            return null;
        }
        $authorizationHeader = request()->header('Authorization');
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Platform-superadmin flag: whether this account operates the SaaS across
     * all tenants (username listed in config('constants.administrator_usernames')).
     * Exposed on the raw model (login/profile) so the client can gate the
     * superadmin panel; explicit resources like UserResource omit it.
     */
    public function getIsSuperadminAttribute(): bool
    {
        if (empty($this->username)) {
            return false;
        }

        $allowed = array_filter(array_map(
            'trim',
            explode(',', (string) config('constants.administrator_usernames')),
        ));

        return in_array($this->username, $allowed, true);
    }

    public function getAvatarUrlAttribute()
    {
        if (! empty($this->avatar)) {
            $avatar_url = asset($this->avatar);
        } else {
            $avatar_url = asset('/assets/user_default.jpg');
        }

        return $avatar_url;
    }

    protected function activityModule(): string
    {
        return 'system';
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function has_roles()
    {
        return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    }
}
