<?php

namespace App\Modules\System\User\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->code,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'department' => $this->department,

            'business_id' => $this->business_id,
            'business' => $this->whenLoaded('business', fn () => [
                'id' => $this->business?->id,
                'name' => $this->business?->name,
            ]),
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),
            'role_id' => $this->role_id,
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role?->id,
                'title' => $this->role?->title,
            ]),

            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'is_admin' => (bool) $this->is_admin,

            'last_login_at' => $this->last_login_at,
            'last_ip' => $this->last_ip,
            'login_count' => $this->login_count,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
