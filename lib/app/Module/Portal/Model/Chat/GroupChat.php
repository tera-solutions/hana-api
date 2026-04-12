<?php

namespace App\Module\Portal\Model\Chat;

use DB;
use Illuminate\Database\Eloquent\Model;


class GroupChat extends Model
{
    protected $table = "group_chats";
    protected $guarded = ['id'];
    protected $appends = ['avatar_url', 'total_member'];

    public function users()
    {
        return $this->hasMany(\App\Module\Portal\Model\Chat\GroupUser::class, 'group_id')->where("type", "user");
    }

    public function getAvatarUrlAttribute()
    {
        if (!empty($this->avatar)) {
            $avatar_url = asset($this->avatar);
        } else {
            $avatar_url = asset('/assets/group.png');
        }

        return $avatar_url;
    }

    public function getTotalMemberAttribute()
    {
        return $this->hasMany(\App\Module\Portal\Model\Chat\GroupUser::class, 'group_id')->where("type", "user")->count("id");
    }
}
