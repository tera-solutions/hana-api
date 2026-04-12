<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Traits\HasRoles;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;
    use HasRoles;
    use HasApiTokens;

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

    protected $appends = ['avatar_url'];

    protected $fillable = [
        'department_id',
        'position_id',
        'status',
        'full_name',
        'username',
        'password',
        'email',
        'is_admin',
        'allow_chat',
        'remember_token',
        'avatar',
        'code',
        'role_id',
        'status_work',
        'business_id',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at',
        'ip',
        'is_login',
        'phone',
        'is_active',
        'business_name',
        'verify_auth',
        'time_block',
        'type',
        'register_time',
        'expiration_time',
        'trial_time',
        'status_account',
        'department',
        'job_title',
        'is_first',
        '_url',
        'reps_login',
        'updated_by',
    ];


    public  function getBearerToken()
    {
        if (!request()->header('Authorization')) {
            return null;
        }
        $authorizationHeader = request()->header('Authorization');
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
    public function getAvatarUrlAttribute()
    {
        if (!empty($this->avatar)) {
            $avatar_url = asset($this->avatar);
        } else {
            $avatar_url = asset('/assets/user_default.jpg');
        }

        return $avatar_url;
    }

    // public function employee()
    // {
    //     return $this->hasOne(\App\Module\HRM\Model\Employee::class);
    // }

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function role()
    {
        return $this->belongsTo(\App\Models\Role::class, 'role_id');
    }

    public function has_roles()
    {
        return $this->hasMany(\App\Models\RolePermission::class, 'role_id', 'role_id');
    }
}
