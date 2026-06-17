<?php

namespace App\Modules\Education\Room\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\Room\Enums\RoomStatus;
use App\Modules\System\Branch\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Room extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'edu_rooms';

    protected $guarded = [];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public const STATUS_ACTIVE = RoomStatus::Active->value;

    public const STATUS_INACTIVE = RoomStatus::Inactive->value;

    public const STATUS_MAINTENANCE = RoomStatus::Maintenance->value;

    /**
     * Related tables that reference this room (room.md §14). Used to enforce the
     * code-immutability and capacity rules.
     *
     * @var array<string, string> table => room foreign key column
     */
    public const LINKED_TABLES = [
        'edu_classes' => 'room_id',
        'edu_sessions' => 'room_id',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'room_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'room_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'room_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RoomHistory::class, 'room_id');
    }
}
