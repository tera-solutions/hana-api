<?php

namespace App\Modules\Education\ClassSchedule\Services;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Services\ClassService;
use App\Modules\Education\ClassSchedule\Models\ClassSchedule;
use Illuminate\Database\Eloquent\Collection;

class ClassScheduleService
{
    public function __construct(private readonly ClassService $classService) {}

    public function list($classId): Collection
    {
        ClassRoom::findOrFail($classId);

        return ClassSchedule::where('class_id', $classId)
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();
    }

    public function find($scheduleId): ClassSchedule
    {
        return ClassSchedule::findOrFail($scheduleId);
    }

    public function create($classId, array $data): ClassSchedule
    {
        ClassRoom::findOrFail($classId);

        $schedule = ClassSchedule::create([
            'class_id' => $classId,
            'weekday' => $data['weekday'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        // Adding a schedule can move a draft class to upcoming/active.
        $this->classService->recomputeStatus($classId);

        return $schedule->fresh();
    }

    public function update($scheduleId, array $data): ClassSchedule
    {
        $schedule = ClassSchedule::findOrFail($scheduleId);
        $schedule->update($data);

        return $schedule->fresh();
    }

    public function delete($scheduleId): void
    {
        $schedule = ClassSchedule::findOrFail($scheduleId);
        $classId = $schedule->class_id;
        $schedule->delete();

        // Removing the last schedule can drop a scheduled class back to draft.
        $this->classService->recomputeStatus($classId);
    }
}
