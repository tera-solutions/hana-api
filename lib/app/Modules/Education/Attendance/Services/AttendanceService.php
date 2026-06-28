<?php

namespace App\Modules\Education\Attendance\Services;

use App\Modules\Education\Attendance\Models\Attendance;
use App\Modules\Education\Support\TeacherScope;
use Package\Database\Concerns\HandlesEntityQueries;

class AttendanceService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['session', 'student'];

    /**
     * Paginated, filterable attendance list ("Danh sách chuyên cần").
     */
    public function paginate(array $params = [])
    {
        $query = Attendance::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        foreach (['session_id', 'student_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['class_id'])) {
            $query->whereHas('session', fn ($q) => $q->where('class_id', $params['class_id']));
        }

        if (! empty($params['date'])) {
            $query->whereHas('session', fn ($q) => $q->whereDate('session_date', $params['date']));
        }
        if (! empty($params['date_from'])) {
            $query->whereHas('session', fn ($q) => $q->whereDate('session_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('session', fn ($q) => $q->whereDate('session_date', '<=', $params['date_to']));
        }

        if ($scope = TeacherScope::current()) {
            $query->whereHas('session', fn ($q) => $scope->constrainSessions($q));
        }

        $this->applySort($query, $params, ['status', 'checkin_time', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }
}
