<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a coherent, linked demo dataset across the System / Education / CRM / Finance
 * modules. Entities that carry a status / type / direction enum get one row per
 * variant so every state is represented and navigable.
 *
 * Standalone (not chained into DatabaseSeeder, which seeds permissions/roles):
 *   php artisan db:seed --class="Database\Seeders\MasterSeeder"
 *
 * Coverage note: the reshaped Finance documents (invoice / payment / debt) are not
 * seeded here — only Accounts — as their schema spans several align migrations with
 * interdependent student/parent/enrollment links.
 */
class MasterSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $businessId = $this->business();
            $branches = $this->branches($businessId);
            $courses = $this->courses($businessId);
            $levels = $this->levels($courses);
            $rooms = $this->rooms($businessId, $branches);
            $teachers = $this->teachers($businessId);
            $students = $this->students($businessId, $branches, $levels);
            $classes = $this->classes($businessId, $courses, $rooms);
            $this->classStudents($classes, $students);
            $this->enrollments($businessId, $classes, $students);
            $lessons = $this->lessons($classes);
            $lessonPlans = $this->lessonPlans($courses);
            $this->materials($courses, $lessonPlans);
            $this->assignments($courses, $classes, $students);
            $this->studentLevels($businessId, $students, $courses, $levels);
            $parents = $this->crm($businessId, $branches, $students);
            $this->accounts($businessId);

            $sessions = $this->sessions($classes, $teachers);
            $this->attendances($sessions, $students);
            $this->leaveRequests($students, $teachers, $classes, $lessons);
            $this->promotions($businessId, $parents);
            $this->evaluations($teachers, $students, $parents, $courses, $classes, $lessons);
            $this->wallets($businessId, $parents);
        });

        $this->command?->info('MasterSeeder: demo data seeded.');
    }

    private function now()
    {
        return now();
    }

    private function business(): int
    {
        return DB::table('sys_business')->insertGetId([
            'name' => 'Hana English (Demo)',
            'email' => 'demo@hana.edu.vn',
            'status' => 'active',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    /** @return int[] */
    private function branches(int $businessId): array
    {
        $ids = [];
        foreach (['Quận 1', 'Quận 7'] as $i => $name) {
            $ids[] = DB::table('sys_branches')->insertGetId([
                'business_id' => $businessId,
                'name' => 'Chi nhánh '.$name,
                'code' => 'CN'.($i + 1),
                'address' => $name.', TP.HCM',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        return $ids;
    }

    /** @return array<int, array{id:int,active:bool}> */
    private function courses(int $businessId): array
    {
        $courses = [];
        foreach ([['Kids English', true], ['IELTS Foundation', true], ['TOEIC (tạm dừng)', false]] as $i => [$name, $active]) {
            $id = DB::table('edu_courses')->insertGetId([
                'business_id' => $businessId,
                'name' => $name,
                'code' => 'CRS'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'duration_minutes' => 90,
                'price_per_lesson' => 200000,
                'is_active' => $active,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
            $courses[] = ['id' => $id, 'active' => $active];
        }

        return $courses;
    }

    /** @return array<int, int[]> course_id => level ids (ordered path) */
    private function levels(array $courses): array
    {
        $path = ['Starter', 'Mover', 'Flyer', 'KET', 'PET'];
        $cefr = ['Pre-A1', 'A1', 'A2', 'B1', 'B2'];
        $byCourse = [];
        foreach ($courses as $course) {
            foreach (array_slice($path, 0, 3) as $order => $name) {
                $byCourse[$course['id']][] = DB::table('edu_levels')->insertGetId([
                    'level_code' => 'LV'.$course['id'].'-'.($order + 1),
                    'level_name' => $name,
                    'course_id' => $course['id'],
                    'level_order' => $order + 1,
                    'cefr_level' => $cefr[$order],
                    'status' => 'active',
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }
        }

        return $byCourse;
    }

    /** @return int[] */
    private function rooms(int $businessId, array $branches): array
    {
        $types = ['classroom', 'computer_room', 'speaking_room', 'exam_room', 'meeting_room', 'other'];
        $statuses = ['active', 'inactive', 'maintenance'];
        $ids = [];
        foreach ($types as $i => $type) {
            $ids[] = DB::table('edu_rooms')->insertGetId([
                'business_id' => $businessId,
                'branch_id' => $branches[$i % count($branches)],
                'room_code' => 'R'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'room_name' => 'Phòng '.($i + 1),
                'room_type' => $type,
                'capacity' => 20 + $i,
                'status' => $statuses[$i % count($statuses)],
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        return $ids;
    }

    /** @return int[] */
    private function teachers(int $businessId): array
    {
        $types = ['full_time', 'part_time', 'freelancer', 'assistant'];
        $statuses = ['active', 'suspended', 'resigned'];
        $ids = [];
        foreach ($types as $i => $type) {
            $ids[] = DB::table('hr_teachers')->insertGetId([
                'business_id' => $businessId,
                'code' => 'GV'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'full_name' => 'Giáo viên '.($i + 1),
                'employment_type' => $type,
                'status' => $statuses[$i % count($statuses)],
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        return $ids;
    }

    /** @return int[] */
    private function students(int $businessId, array $branches, array $levels): array
    {
        $statuses = ['active', 'suspended', 'graduated', 'dropped'];
        $levelPool = array_merge(...array_values($levels));
        $ids = [];
        foreach ($statuses as $i => $status) {
            // Two students per status for richer linking.
            foreach (range(1, 2) as $n) {
                $seq = count($ids) + 1;
                $ids[] = DB::table('edu_students')->insertGetId([
                    'business_id' => $businessId,
                    'branch_id' => $branches[$seq % count($branches)],
                    'code' => 'STU'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                    'name' => 'Học viên '.$seq,
                    'status' => $status,
                    'level_id' => $levelPool[$seq % count($levelPool)],
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }
        }

        return $ids;
    }

    /** @return int[] */
    private function classes(int $businessId, array $courses, array $rooms): array
    {
        $statuses = ['draft', 'upcoming', 'active', 'suspended', 'completed'];
        $learningTypes = ['scheduled', 'self_learning', 'flexible'];
        $ids = [];
        foreach ($statuses as $i => $status) {
            $course = $courses[$i % count($courses)];
            $ids[] = DB::table('edu_classes')->insertGetId([
                'business_id' => $businessId,
                'course_id' => $course['id'],
                'room_id' => $rooms[$i % count($rooms)],
                'code' => 'CLS'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'name' => 'Lớp '.($i + 1),
                'learning_type' => $learningTypes[$i % count($learningTypes)],
                'status' => $status,
                'start_date' => now()->addDays($i * 7)->toDateString(),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        return $ids;
    }

    private function classStudents(array $classes, array $students): void
    {
        $statuses = ['active', 'reserved', 'completed', 'dropped', 'transferred_out'];
        foreach ($statuses as $i => $status) {
            DB::table('edu_class_students')->insert([
                'class_id' => $classes[$i % count($classes)],
                'student_id' => $students[$i % count($students)],
                'status' => $status,
                'enrolled_at' => now()->subDays(30 - $i)->toDateString(),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }

    private function enrollments(int $businessId, array $classes, array $students): void
    {
        $statuses = ['pending', 'studying', 'suspended', 'transferred', 'completed', 'cancelled', 'refunded'];
        foreach ($statuses as $i => $status) {
            DB::table('edu_enrollments')->insert([
                'business_id' => $businessId,
                'student_id' => $students[$i % count($students)],
                'class_id' => $classes[$i % count($classes)],
                'enrolled_at' => now()->subDays(20 - $i)->toDateString(),
                'status' => $status,
                'progress' => $i * 10,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }

    /** @return array<int, int[]> class_id => lesson ids */
    private function lessons(array $classes): array
    {
        $statuses = ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'locked'];
        $byClass = [];
        foreach ($classes as $classId) {
            foreach ($statuses as $i => $status) {
                $byClass[$classId][] = DB::table('edu_lessons')->insertGetId([
                    'class_room_id' => $classId,
                    'lesson_no' => $i + 1,
                    'lesson_title' => 'Buổi '.($i + 1),
                    'lesson_date' => now()->addDays($i)->toDateString(),
                    'start_time' => '18:00',
                    'end_time' => '19:30',
                    'status' => $status,
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }
        }

        return $byClass;
    }

    /** @return int[] */
    private function lessonPlans(array $courses): array
    {
        $statuses = ['draft', 'reviewing', 'published', 'archived'];
        $ids = [];
        foreach ($statuses as $i => $status) {
            $ids[] = DB::table('edu_lesson_plans')->insertGetId([
                'plan_code' => 'LP'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'plan_name' => 'Giáo án '.($i + 1),
                'course_id' => $courses[$i % count($courses)]['id'],
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        return $ids;
    }

    private function materials(array $courses, array $lessonPlans): void
    {
        $types = ['pdf', 'video', 'audio', 'slide', 'worksheet', 'homework'];
        $access = ['teacher', 'student', 'parent', 'internal'];
        $statuses = ['draft', 'active'];
        foreach ($types as $i => $type) {
            $status = $statuses[$i % count($statuses)];
            $materialId = DB::table('edu_materials')->insertGetId([
                'material_code' => 'MAT'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'material_name' => 'Tài liệu '.($i + 1),
                'material_type' => $type,
                'access_type' => $access[$i % count($access)],
                'current_version' => 1,
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            DB::table('edu_material_versions')->insert([
                'material_id' => $materialId,
                'version' => 1,
                'file_name' => 'tai-lieu-'.($i + 1).'.'.$type,
                'change_log' => 'Phiên bản đầu tiên',
                'created_at' => $this->now(),
            ]);

            // Where-used: link to a course and a lesson plan.
            DB::table('edu_material_mappings')->insert([
                ['material_id' => $materialId, 'entity_type' => 'course', 'entity_id' => $courses[$i % count($courses)]['id'], 'created_at' => $this->now(), 'updated_at' => $this->now()],
                ['material_id' => $materialId, 'entity_type' => 'lesson_plan', 'entity_id' => $lessonPlans[$i % count($lessonPlans)], 'created_at' => $this->now(), 'updated_at' => $this->now()],
            ]);
        }
    }

    private function assignments(array $courses, array $classes, array $students): void
    {
        $types = ['homework', 'worksheet', 'quiz', 'writing', 'speaking', 'listening', 'reading', 'project', 'exam_practice'];
        $statuses = ['draft', 'published', 'closed'];
        $submissionStatuses = ['assigned', 'submitted', 'late_submitted', 'graded', 'reviewed'];
        foreach ($types as $i => $type) {
            $status = $statuses[$i % count($statuses)];
            $assignmentId = DB::table('edu_assignments')->insertGetId([
                'assignment_code' => 'ASG'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'assignment_name' => 'Bài tập '.($i + 1),
                'assignment_type' => $type,
                'course_id' => $courses[$i % count($courses)]['id'],
                'class_room_id' => $classes[$i % count($classes)],
                'instruction' => 'Hoàn thành bài tập '.($i + 1),
                'max_score' => 10,
                'due_date' => now()->addDays(7 + $i),
                'allow_late_submission' => true,
                'allow_multiple_submission' => false,
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            // Only published/closed assignments are handed out.
            if ($status === 'draft') {
                continue;
            }

            foreach ($submissionStatuses as $s => $subStatus) {
                $studentId = $students[($i + $s) % count($students)];
                DB::table('edu_assignment_targets')->insert([
                    'assignment_id' => $assignmentId,
                    'student_id' => $studentId,
                    'assigned_at' => $this->now(),
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
                DB::table('edu_assignment_submissions')->insert([
                    'assignment_id' => $assignmentId,
                    'student_id' => $studentId,
                    'status' => $subStatus,
                    'result_published' => in_array($subStatus, ['graded', 'reviewed'], true),
                    'score' => in_array($subStatus, ['graded', 'reviewed'], true) ? 8.5 : null,
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }
        }
    }

    private function studentLevels(int $businessId, array $students, array $courses, array $levels): void
    {
        $courseId = $courses[0]['id'];
        $path = $levels[$courseId];
        // Place the first 4 students at successive levels, with a history + assessment trail.
        foreach (array_slice($students, 0, 4) as $i => $studentId) {
            $toLevel = $path[min($i, count($path) - 1)];
            $fromLevel = $i > 0 ? $path[$i - 1] : null;

            $studentLevelId = DB::table('edu_student_levels')->insertGetId([
                'business_id' => $businessId,
                'student_id' => $studentId,
                'course_id' => $courseId,
                'level_id' => $toLevel,
                'assigned_at' => $this->now(),
                'placement_score' => 30 + $i * 15,
                'status' => 'active',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            DB::table('edu_student_level_assessments')->insert([
                'student_id' => $studentId,
                'assessment_type' => 'placement_test',
                'score' => 30 + $i * 15,
                'level_id' => $toLevel,
                'comment' => 'Đánh giá đầu vào',
                'assessed_at' => $this->now(),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            DB::table('edu_student_level_histories')->insert([
                'student_level_id' => $studentLevelId,
                'business_id' => $businessId,
                'student_id' => $studentId,
                'from_level_id' => $fromLevel,
                'to_level_id' => $toLevel,
                'source' => $i === 0 ? 'placement' : 'promote',
                'action' => $i === 0 ? 'placement' : 'promote',
                'reason' => $i === 0 ? 'Xếp lớp đầu vào' : 'Đủ điều kiện lên cấp',
                'effective_at' => $this->now(),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }

    /** @return int[] parent ids */
    private function crm(int $businessId, array $branches, array $students): array
    {
        foreach (['pending', 'verified', 'studying', 'inactive'] as $i => $status) {
            DB::table('crm_leads')->insert([
                'business_id' => $businessId,
                'branch_id' => $branches[$i % count($branches)],
                'code' => 'LEAD'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'name' => 'Tiềm năng '.($i + 1),
                'phone' => '09000000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        $parentIds = [];
        foreach (['active', 'suspended', 'inactive'] as $i => $status) {
            $parentIds[] = DB::table('crm_parents')->insertGetId([
                'business_id' => $businessId,
                'branch_id' => $branches[$i % count($branches)],
                'code' => 'PAR'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'name' => 'Phụ huynh '.($i + 1),
                'phone' => '09111111'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        foreach (['father', 'mother'] as $i => $relation) {
            DB::table('crm_parent_student')->insert([
                'parent_id' => $parentIds[$i % count($parentIds)],
                'student_id' => $students[$i % count($students)],
                'relation' => $relation,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        return $parentIds;
    }

    private function accounts(int $businessId): void
    {
        foreach (['cash' => 'Tiền mặt', 'bank' => 'Ngân hàng', 'ewallet' => 'Ví điện tử'] as $type => $name) {
            DB::table('fin_accounts')->insert([
                'business_id' => $businessId,
                'code' => 'ACC-'.strtoupper($type),
                'name' => $name,
                'type' => $type,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }

    /**
     * Class sessions (buổi học) — one per status variant. @return int[]
     */
    private function sessions(array $classes, array $teachers): array
    {
        $statuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];
        $ids = [];
        foreach ($statuses as $i => $status) {
            $ids[] = DB::table('edu_sessions')->insertGetId([
                'class_id' => $classes[$i % count($classes)],
                'teacher_id' => $teachers[$i % count($teachers)],
                'session_no' => $i + 1,
                'code' => 'SES'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'name' => 'Buổi học '.($i + 1),
                'session_date' => now()->addDays($i)->toDateString(),
                'start_time' => '18:00',
                'end_time' => '19:30',
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        return $ids;
    }

    /**
     * Per-student attendance ("chuyên cần") — one row per status variant. The
     * (session, student) pair is unique, so each row uses a distinct combination.
     */
    private function attendances(array $sessions, array $students): void
    {
        $statuses = ['present', 'absent', 'late', 'excused'];
        foreach ($statuses as $i => $status) {
            DB::table('edu_attendances')->insert([
                'session_id' => $sessions[$i % count($sessions)],
                'student_id' => $students[$i % count($students)],
                'status' => $status,
                'checkin_time' => in_array($status, ['present', 'late'], true)
                    ? now()->setTime(18, $status === 'late' ? 20 : 0)
                    : null,
                'checkout_time' => $status === 'present' ? now()->setTime(19, 30) : null,
                'note' => $status === 'excused' ? 'Vắng có phép' : null,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }

    /**
     * Leave requests — one per status variant (a teacher leave among them). An approved
     * student leave raises make-up entitlements covering every make-up status, and each
     * request gets a creation log.
     */
    private function leaveRequests(array $students, array $teachers, array $classes, array $lessons): void
    {
        $reasonTypes = ['sick', 'family', 'school_activity', 'vacation', 'personal'];
        $statuses = ['pending', 'approved', 'rejected', 'cancelled', 'completed'];

        foreach ($statuses as $i => $status) {
            $classId = $classes[$i % count($classes)];
            $classLessons = $lessons[$classId] ?? [];
            $lessonId = $classLessons[0] ?? null;
            $isTeacher = $i === 2; // one teacher_leave variant
            $requesterId = $isTeacher ? $teachers[0] : $students[$i % count($students)];

            $leaveId = DB::table('edu_leave_requests')->insertGetId([
                'request_code' => 'LR'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'request_type' => $isTeacher ? 'teacher_leave' : 'student_leave',
                'requester_type' => $isTeacher ? 'teacher' : 'student',
                'requester_id' => $requesterId,
                'class_room_id' => $classId,
                'lesson_id' => $lessonId,
                'leave_date' => now()->addDays($i)->toDateString(),
                'reason_type' => $reasonTypes[$i % count($reasonTypes)],
                'reason' => 'Lý do nghỉ '.($i + 1),
                'status' => $status,
                'approved_at' => in_array($status, ['approved', 'completed'], true) ? $this->now() : null,
                'rejection_reason' => $status === 'rejected' ? 'Không đủ điều kiện' : null,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            DB::table('edu_leave_request_logs')->insert([
                'leave_request_id' => $leaveId,
                'action' => 'created',
                'old_status' => null,
                'new_status' => 'pending',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            // BR007: an approved student leave earns make-up entitlements.
            if ($status === 'approved' && ! $isTeacher) {
                foreach (['waiting', 'scheduled', 'completed', 'expired'] as $mStatus) {
                    DB::table('edu_makeup_lessons')->insert([
                        'leave_request_id' => $leaveId,
                        'student_id' => $requesterId,
                        'original_lesson_id' => $lessonId,
                        'makeup_lesson_id' => in_array($mStatus, ['scheduled', 'completed'], true)
                            ? ($classLessons[1] ?? null)
                            : null,
                        'status' => $mStatus,
                        'created_at' => $this->now(),
                        'updated_at' => $this->now(),
                    ]);
                }
            }
        }
    }

    /**
     * Promotion programmes — one per status variant — plus eligibility rules, reward
     * lines, vouchers (one per status), a usage entry and referrals (one per status)
     * on the active programme.
     */
    private function promotions(int $businessId, array $parents): void
    {
        $statuses = ['draft', 'pending', 'active', 'paused', 'expired', 'closed'];
        $types = ['discount', 'voucher', 'gift_lesson', 'wallet_credit', 'combo', 'referral'];

        $promotionIds = [];
        foreach ($statuses as $i => $status) {
            $type = $types[$i % count($types)];
            $promotionIds[$status] = DB::table('fin_promotions')->insertGetId([
                'promotion_code' => 'PROMO'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'promotion_name' => 'Khuyến mãi '.($i + 1),
                'promotion_type' => $type,
                'start_date' => now()->subDays(10)->toDateString(),
                'end_date' => now()->addDays(20 + $i)->toDateString(),
                'status' => $status,
                'priority' => $i,
                'discount_type' => 'percent',
                'discount_value' => 10 + $i,
                'max_discount' => 500000,
                'bonus_lesson' => $type === 'gift_lesson' ? 2 : null,
                'bonus_wallet_amount' => $type === 'wallet_credit' ? 100000 : null,
                'approved_at' => in_array($status, ['active', 'paused', 'expired', 'closed'], true) ? $this->now() : null,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        $activeId = $promotionIds['active'];

        foreach (['min_order' => '5000000', 'new_customer' => '1', 'first_enrollment' => '1', 'course' => '1', 'level' => '1', 'branch' => '1'] as $ruleType => $ruleValue) {
            DB::table('fin_promotion_rules')->insert([
                'promotion_id' => $activeId,
                'rule_type' => $ruleType,
                'rule_value' => $ruleValue,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        foreach (['discount' => '10', 'gift_lesson' => '2', 'wallet_credit' => '100000', 'voucher' => '1'] as $rewardType => $rewardValue) {
            DB::table('fin_promotion_rewards')->insert([
                'promotion_id' => $activeId,
                'reward_type' => $rewardType,
                'reward_value' => $rewardValue,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        $voucherIds = [];
        foreach (['active', 'used', 'expired', 'locked'] as $i => $vStatus) {
            $voucherIds[$vStatus] = DB::table('fin_vouchers')->insertGetId([
                'promotion_id' => $activeId,
                'voucher_code' => 'HANA'.strtoupper(substr(md5($vStatus.$i), 0, 8)),
                'usage_limit' => 5,
                'used_count' => match ($vStatus) {
                    'used' => 5,
                    'active' => 1,
                    default => 0,
                },
                'expired_at' => $vStatus === 'expired' ? now()->subDay() : now()->addDays(30),
                'status' => $vStatus,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        DB::table('fin_promotion_usages')->insert([
            'promotion_id' => $activeId,
            'voucher_id' => $voucherIds['used'],
            'discount_amount' => 200000,
            'used_at' => $this->now(),
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        if (count($parents) >= 2) {
            foreach (['pending', 'rewarded', 'cancelled'] as $i => $rStatus) {
                DB::table('fin_referrals')->insert([
                    'referrer_parent_id' => $parents[0],
                    'referred_parent_id' => $parents[1 + ($i % (count($parents) - 1))],
                    'promotion_id' => $activeId,
                    'reward_amount' => 100000,
                    'status' => $rStatus,
                    'rewarded_at' => $rStatus === 'rewarded' ? $this->now() : null,
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }
        }
    }

    /**
     * Evaluations — teacher / student / parent, covering every status and a spread of
     * classifications. The total score + classification are computed from the criteria
     * (as the service does), since this seeder writes via the query builder.
     */
    private function evaluations(array $teachers, array $students, array $parents, array $courses, array $classes, array $lessons): void
    {
        $courseId = $courses[0]['id'];
        $classId = $classes[0];
        $lessonId = $lessons[$classId][0] ?? null;

        $criteriaKeys = [
            'teacher' => ['expertise', 'teaching_method', 'communication', 'attitude'],
            'student' => ['knowledge', 'pronunciation', 'grammar', 'homework'],
            'parent' => ['cooperation', 'learning_follow_up', 'on_time_payment', 'feedback'],
        ];

        // [type, target id, evaluator type, evaluator id, period, criterion scores, status].
        $rows = [
            ['teacher', $teachers[0], 'student', $students[0], 'course', [5, 5, 4, 5], 'locked'],
            ['teacher', $teachers[1], 'parent', $parents[0], 'monthly', [4, 4, 3, 4], 'approved'],
            ['teacher', $teachers[2], 'manager', 1, 'quarterly', [3, 3, 2, 3], 'submitted'],

            ['student', $students[0], 'teacher', $teachers[0], 'final', [5, 4, 5, 5], 'approved'],
            ['student', $students[1], 'teacher', $teachers[0], 'midterm', [3, 3, 3, 2], 'draft'],
            ['student', $students[2], 'teacher', $teachers[1], 'lesson', [2, 2, 1, 2], 'rejected'],

            ['parent', $parents[0], 'manager', 1, 'monthly', [4, 4, 5, 4], 'approved'],
            ['parent', $parents[1], 'cskh', 1, 'quarterly', [2, 2, 1, 2], 'draft'],
        ];

        foreach ($rows as $seq => [$type, $targetId, $evaluatorType, $evaluatorId, $period, $scores, $status]) {
            $keys = $criteriaKeys[$type];
            $criteria = [];
            foreach ($scores as $i => $score) {
                $criteria[] = ['criterion' => $keys[$i % count($keys)], 'score' => $score];
            }
            $average = round(array_sum($scores) / count($scores), 2);

            DB::table('edu_evaluations')->insert([
                'evaluation_code' => 'EVAL'.str_pad((string) ($seq + 1), 6, '0', STR_PAD_LEFT),
                'evaluation_type' => $type,
                'target_id' => $targetId,
                'evaluator_type' => $evaluatorType,
                'evaluator_id' => $evaluatorId,
                'course_id' => $courseId,
                'class_room_id' => $type === 'parent' ? null : $classId,
                'lesson_id' => $period === 'lesson' ? $lessonId : null,
                'evaluation_period' => $period,
                'criteria' => json_encode($criteria),
                'score' => $average,
                'classification' => $this->classifyEvaluation($type, $average),
                'comment' => 'Ghi chú đánh giá '.($seq + 1),
                'status' => $status,
                'evaluated_at' => $this->now(),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }

    private function classifyEvaluation(string $type, float $average): string
    {
        return match (true) {
            $average >= 4.5 => 'excellent',
            $average >= 3.5 => 'good',
            $average >= 2.5 => 'average',
            default => $type === 'parent' ? 'warning' : 'weak',
        };
    }

    /**
     * Wallets — one per parent (BR001), covering each status. The active wallet carries a
     * coherent ledger trail (deposit → bonus → payment → adjustment) whose final
     * balance_after matches the wallet's spendable balance, plus an adjustment record.
     */
    private function wallets(int $businessId, array $parents): void
    {
        // [available, bonus, frozen, status] — the active wallet's available reflects its trail.
        $configs = [
            [525000, 0, 0, 'active'],
            [200000, 0, 0, 'locked'],
            [0, 0, 0, 'closed'],
        ];

        $txnSeq = 0;
        foreach (array_values($parents) as $i => $parentId) {
            [$available, $bonus, $frozen, $status] = $configs[$i % count($configs)];

            $walletId = DB::table('fin_wallets')->insertGetId([
                'business_id' => $businessId,
                'wallet_code' => 'WAL'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'owner_type' => 'parent',
                'owner_id' => $parentId,
                'available_balance' => $available,
                'bonus_balance' => $bonus,
                'frozen_balance' => $frozen,
                'currency' => 'VND',
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            if ($status !== 'active') {
                continue;
            }

            // deposit 500k → bonus +50k → payment 50k (bonus spent first) → adjustment +25k.
            $trail = [
                ['deposit', 500000, 0, 500000],
                ['bonus', 50000, 500000, 550000],
                ['payment', 50000, 550000, 500000],
                ['adjustment', 25000, 500000, 525000],
            ];
            foreach ($trail as [$type, $amount, $before, $after]) {
                $txnSeq++;
                DB::table('fin_wallet_transactions')->insert([
                    'business_id' => $businessId,
                    'wallet_id' => $walletId,
                    'transaction_code' => 'WTX'.str_pad((string) $txnSeq, 6, '0', STR_PAD_LEFT),
                    'transaction_type' => $type,
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'description' => 'Demo '.$type,
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }

            DB::table('fin_wallet_adjustments')->insert([
                'wallet_id' => $walletId,
                'adjustment_type' => 'increase',
                'amount' => 25000,
                'reason' => 'Bù trừ đối soát',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }
}
