<?php

namespace Package\Database\Concerns;

/**
 * Exposes a computed `avatar_url` (a full, asset()-resolved URL with a default
 * fallback) from the model's stored `avatar` path, mirroring App\Models\User.
 *
 *   - Migration:  $table->string('avatar')->nullable();
 *   - Model:      use HasAvatarUrl;  + protected $appends = ['avatar_url'];
 */
trait HasAvatarUrl
{
    public function getAvatarUrlAttribute(): string
    {
        return ! empty($this->avatar)
            ? asset($this->avatar)
            : asset('/assets/user_default.jpg');
    }
}
