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
            $this->lessons($classes);
            $lessonPlans = $this->lessonPlans($courses);
            $this->materials($courses, $lessonPlans);
            $this->assignments($courses, $classes, $students);
            $this->studentLevels($businessId, $students, $courses, $levels);
            $this->crm($businessId, $branches, $students);
            $this->accounts($businessId);
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

    private function lessons(array $classes): void
    {
        $statuses = ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'locked'];
        foreach ($classes as $classId) {
            foreach ($statuses as $i => $status) {
                DB::table('edu_lessons')->insert([
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

    private function crm(int $businessId, array $branches, array $students): void
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
}
