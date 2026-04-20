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
