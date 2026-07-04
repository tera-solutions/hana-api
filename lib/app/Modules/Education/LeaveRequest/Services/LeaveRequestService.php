<?php

namespace App\Modules\Education\LeaveRequest\Services;

use App\Helpers\Task;
use App\Modules\Education\LeaveRequest\Enums\LeaveRequestType;
use App\Modules\Education\LeaveRequest\Models\LeaveRequest;
use App\Modules\Education\LeaveRequest\Models\LeaveRequestLog;
use App\Modules\Education\LeaveRequest\Models\MakeupLesson;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;
use Package\Exception\AuthorizationException;

class LeaveRequestService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['classRoom', 'lesson', 'makeups'];

    /**
     * Paginated, filterable list (leave-request.md §XIII).
     */
    public function paginate(array $params = [])
    {
        $query = LeaveRequest::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('request_code', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        foreach (['request_type', 'requester_type', 'requester_id', 'class_room_id', 'lesson_id', 'reason_type', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['leave_date'])) {
            $query->whereDate('leave_date', $params['leave_date']);
        }
        if (! empty($params['leave_date_from'])) {
            $query->whereDate('leave_date', '>=', $params['leave_date_from']);
        }
        if (! empty($params['leave_date_to'])) {
            $query->whereDate('leave_date', '<=', $params['leave_date_to']);
        }

        if ($scope = TeacherScope::current()) {
            $scope->constrainLeaveRequests($query);
        }

        $this->applySort($query, $params, ['request_code', 'leave_date', 'status', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    public function find($id): LeaveRequest
    {
        $query = LeaveRequest::query();

        if ($scope = TeacherScope::current()) {
            $scope->constrainLeaveRequests($query);
        }

        return $query->with([...self::RELATIONS, 'logs'])->findOrFail($id);
    }

    /**
     * Create a pending leave request, enforcing the lesson rules BR001–BR004.
     *
     * @throws \RuntimeException
     */
    public function create(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $type = LeaveRequestType::from($data['request_type']);
            $data['requester_type'] = $type->requesterType();

            $this->assertRequesterExists($data['requester_type'], (int) $data['requester_id']);

            $lesson = ! empty($data['lesson_id']) ? Lesson::findOrFail($data['lesson_id']) : null;

            if ($lesson) {
                $this->assertLessonBookable($lesson, $data);
                $data['class_room_id'] = $data['class_room_id'] ?? $lesson->class_room_id;
            }

            if ($scope = TeacherScope::current()) {
                if ($data['requester_type'] === LeaveRequestType::TeacherLeave->requesterType()) {
                    if ((int) $data['requester_id'] !== $scope->teacherId) {
                        throw new AuthorizationException('Bạn chỉ có thể tạo đơn nghỉ cho chính mình.');
                    }
                } else {
                    $scope->authorizeStudent((int) $data['requester_id']);
                }
            }

            $request = new LeaveRequest($data);
            $request->request_code = $this->generateCode();
            $request->status = LeaveRequest::STATUS_PENDING;
            $request->save();

            $this->log($request, 'created', null, $request->status);

            return $this->find($request->id);
        });
    }

    /**
     * Update an editable (still pending) leave request.
     *
     * @throws \RuntimeException
     */
    public function update($id, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($id, $data) {
            $request = $this->find($id);

            if ($request->status !== LeaveRequest::STATUS_PENDING) {
                throw new \RuntimeException('Chỉ có thể chỉnh sửa đơn đang chờ duyệt.');
            }

            // Identity, requester and workflow fields are immutable here.
            unset(
                $data['id'], $data['request_code'], $data['request_type'], $data['requester_type'],
                $data['requester_id'], $data['status'], $data['approved_by'], $data['approved_at'],
                $data['rejection_reason']
            );

            if (array_key_exists('lesson_id', $data) && ! empty($data['lesson_id'])) {
                $lesson = Lesson::findOrFail($data['lesson_id']);
                $this->assertLessonBookable($lesson, [
                    'leave_date' => $data['leave_date'] ?? $request->leave_date->toDateString(),
                    'class_room_id' => $data['class_room_id'] ?? $request->class_room_id,
                    'requester_type' => $request->requester_type,
                    'requester_id' => $request->requester_id,
                ], $request->id);
                $data['class_room_id'] = $data['class_room_id'] ?? $lesson->class_room_id;
            }

            $request->fill($data)->save();

            $this->log($request, 'updated');

            return $this->find($request->id);
        });
    }

    /**
     * Approve a pending request. A lesson that has already taken place cannot be
     * approved (BR010); an approved student leave raises a make-up entitlement (BR007).
     *
     * @throws \RuntimeException
     */
    public function approve($id): LeaveRequest
    {
        return DB::transaction(function () use ($id) {
            $request = $this->find($id);

            if ($request->status !== LeaveRequest::STATUS_PENDING) {
                throw new \RuntimeException('Chỉ có thể duyệt đơn đang chờ duyệt.');
            }
            if ($this->lessonAlreadyOccurred($request)) {
                throw new \RuntimeException('Không thể duyệt đơn cho buổi học đã diễn ra.'); // BR010
            }

            $from = $request->status;
            $request->update([
                'status' => LeaveRequest::STATUS_APPROVED,
                'approved_by' => $this->actingUserId(),
                'approved_at' => now(),
            ]);

            // BR007: an approved student leave may earn a make-up session.
            if ($request->isStudentLeave()) {
                MakeupLesson::create([
                    'leave_request_id' => $request->id,
                    'student_id' => $request->requester_id,
                    'original_lesson_id' => $request->lesson_id,
                    'status' => MakeupLesson::STATUS_WAITING,
                ]);
            }

            $this->log($request, 'approved', $from, $request->status);

            return $this->find($request->id);
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function reject($id, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($id, $data) {
            $request = $this->find($id);

            if ($request->status !== LeaveRequest::STATUS_PENDING) {
                throw new \RuntimeException('Chỉ có thể từ chối đơn đang chờ duyệt.');
            }

            $from = $request->status;
            $request->update([
                'status' => LeaveRequest::STATUS_REJECTED,
                'approved_by' => $this->actingUserId(),
                'approved_at' => now(),
                'rejection_reason' => $data['rejection_reason'] ?? null,
            ]);

            $this->log($request, 'rejected', $from, $request->status, $data['rejection_reason'] ?? null);

            return $this->find($request->id);
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function cancel($id, array $data = []): LeaveRequest
    {
        return DB::transaction(function () use ($id, $data) {
            $request = $this->find($id);

            if (! in_array($request->status, [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED], true)) {
                throw new \RuntimeException('Chỉ có thể hủy đơn đang chờ duyệt hoặc đã duyệt.');
            }

            $from = $request->status;
            $request->update(['status' => LeaveRequest::STATUS_CANCELLED]);

            // Pending make-up entitlements die with the request.
            MakeupLesson::where('leave_request_id', $request->id)
                ->where('status', MakeupLesson::STATUS_WAITING)
                ->update(['status' => MakeupLesson::STATUS_EXPIRED]);

            $this->log($request, 'cancelled', $from, $request->status, $data['note'] ?? null);

            return $this->find($request->id);
        });
    }

    /**
     * Schedule a make-up session against a waiting entitlement (leave-request.md §X).
     *
     * @throws \RuntimeException
     */
    public function scheduleMakeup($makeupId, array $data): MakeupLesson
    {
        return DB::transaction(function () use ($makeupId, $data) {
            $makeup = MakeupLesson::findOrFail($makeupId);

            if (! in_array($makeup->status, [MakeupLesson::STATUS_WAITING, MakeupLesson::STATUS_SCHEDULED], true)) {
                throw new \RuntimeException('Chỉ có thể xếp lịch học bù khi đang chờ hoặc đã xếp lịch.');
            }

            Lesson::findOrFail($data['makeup_lesson_id']);

            $makeup->update([
                'makeup_lesson_id' => $data['makeup_lesson_id'],
                'status' => $data['status'] ?? MakeupLesson::STATUS_SCHEDULED,
            ]);

            return $makeup->fresh(['leaveRequest', 'student', 'originalLesson', 'makeupLesson']);
        });
    }

    /**
     * @return Collection<int, MakeupLesson>
     */
    public function makeupsFor($leaveRequestId)
    {
        return MakeupLesson::where('leave_request_id', $leaveRequestId)
            ->with(['originalLesson', 'makeupLesson'])
            ->latest()
            ->get();
    }

    /**
     * BR001 (not completed), BR002 (date/class match), BR003 (no duplicate), BR004 (not cancelled).
     *
     * @throws \RuntimeException
     */
    private function assertLessonBookable(Lesson $lesson, array $data, ?int $ignoreId = null): void
    {
        if ($lesson->status === Lesson::STATUS_COMPLETED) {
            throw new \RuntimeException('Không thể tạo đơn cho buổi học đã hoàn thành.'); // BR001
        }
        if ($lesson->status === Lesson::STATUS_CANCELLED) {
            throw new \RuntimeException('Không thể tạo đơn cho buổi học đã bị hủy.'); // BR004
        }

        $leaveDate = Carbon::parse($data['leave_date'])->toDateString();
        if ($lesson->lesson_date->toDateString() !== $leaveDate) {
            throw new \RuntimeException('Ngày nghỉ phải trùng với ngày của buổi học.'); // BR002
        }

        if (! empty($data['class_room_id']) && (int) $data['class_room_id'] !== (int) $lesson->class_room_id) {
            throw new \RuntimeException('Buổi học không thuộc lớp học đã chọn.'); // BR002
        }

        $duplicate = LeaveRequest::where('lesson_id', $lesson->id)
            ->where('requester_type', $data['requester_type'])
            ->where('requester_id', $data['requester_id'])
            ->whereNotIn('status', [LeaveRequest::STATUS_REJECTED, LeaveRequest::STATUS_CANCELLED])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($duplicate) {
            throw new \RuntimeException('Đã tồn tại đơn nghỉ cho buổi học này.'); // BR003
        }
    }

    private function lessonAlreadyOccurred(LeaveRequest $request): bool
    {
        if ($request->lesson_id) {
            $lesson = Lesson::find($request->lesson_id);
            if ($lesson && in_array($lesson->status, [Lesson::STATUS_COMPLETED, Lesson::STATUS_IN_PROGRESS, Lesson::STATUS_LOCKED], true)) {
                return true;
            }
        }

        return $request->leave_date->startOfDay()->isPast() && ! $request->leave_date->isToday();
    }

    /**
     * @throws \RuntimeException
     */
    private function assertRequesterExists(string $requesterType, int $requesterId): void
    {
        $table = $requesterType === 'teacher' ? 'hr_teachers' : 'edu_students';

        if (! DB::table($table)->where('id', $requesterId)->exists()) {
            throw new \RuntimeException('Người gửi đơn không tồn tại.');
        }
    }

    private function generateCode(): string
    {
        $count = Task::setAndGetReferenceCount('leave_request');

        return Task::generateReferenceNumber('leave_request', $count, 'LR');
    }

    private function log(LeaveRequest $request, string $action, $from = null, $to = null, $note = null): void
    {
        LeaveRequestLog::create([
            'leave_request_id' => $request->id,
            'action' => $action,
            'old_status' => $from,
            'new_status' => $to,
            'note' => $note,
            'created_by' => $this->actingUserId(),
        ]);
    }

    private function actingUserId(): int|string|null
    {
        return Auth::guard('api')->id() ?? Auth::id();
    }
}
