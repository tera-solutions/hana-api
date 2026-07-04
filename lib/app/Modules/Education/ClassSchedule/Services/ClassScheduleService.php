<?php

namespace App\Modules\Education\ClassSchedule\Services;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Services\ClassService;
use App\Modules\Education\ClassSchedule\Models\ClassSchedule;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Database\Eloquent\Collection;

class ClassScheduleService
{
    public function __construct(private readonly ClassService $classService) {}

    public function list($classId): Collection
    {
        ClassRoom::findOrFail($classId);

        if ($scope = TeacherScope::current()) {
            $scope->authorizeClass((int) $classId);
        }

        return ClassSchedule::where('class_id', $classId)
            ->with('eduClass')
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();
    }

    public function find($scheduleId): ClassSchedule
    {
        $schedule = ClassSchedule::with('eduClass')->findOrFail($scheduleId);

        if ($scope = TeacherScope::current()) {
            $scope->authorizeClass((int) $schedule->class_id);
        }

        return $schedule;
    }

    public function create($classId, array $data): ClassSchedule
    {
        ClassRoom::findOrFail($classId);

        if ($scope = TeacherScope::current()) {
            $scope->authorizeClass((int) $classId);
        }

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

        if ($scope = TeacherScope::current()) {
            $scope->authorizeClass((int) $schedule->class_id);
        }

        $schedule->update($data);

        return $schedule->fresh();
    }

    public function delete($scheduleId): void
    {
        $schedule = ClassSchedule::findOrFail($scheduleId);

        if ($scope = TeacherScope::current()) {
            $scope->authorizeClass((int) $schedule->class_id);
        }

        $classId = $schedule->class_id;
        $schedule->delete();

        // Removing the last schedule can drop a scheduled class back to draft.
        $this->classService->recomputeStatus($classId);
    }
}
