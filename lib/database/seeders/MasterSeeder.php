<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a coherent, fully-linked demo dataset across the System / Education / CRM / Finance
 * modules. Entities that carry a status / type / direction enum get one row per variant so
 * every state is represented, and every FK is wired to a real seeded row (or, for actor
 * columns, to one of the existing users below). Descriptive columns are all populated.
 *
 * Standalone (not chained into DatabaseSeeder, which seeds permissions/roles):
 *   php artisan db:seed --class="Database\Seeders\MasterSeeder"
 *
 * Assumptions:
 *   - Users 1..4 already exist (BusinessAndUserSeeder) and back every actor FK
 *     (created_by / manager_id / assignee_id / sales_id / …). No new users are created.
 *   - Media rows 1..7 already exist and back file_id references.
 *   - Soft-delete columns (deleted_at / deleted_by) stay NULL by design.
 *
 * Coverage note: the reshaped Finance documents (invoice / payment / debt) are not seeded
 * here — only Accounts — as their schema spans several align migrations with interdependent
 * student/parent/enrollment links.
 */
class MasterSeeder extends Seeder
{
    /** Existing users backing every actor FK. */
    private const CREATOR = 1;      // Super Admin — created_by / updated_by

    private const MANAGER = 2;      // Admin — manager_id / approvals

    private const TEACHER_USER = 3; // Cô Hạ — teacher account / assessor

    private const SALES = 4;        // Nguyễn Van A — sales / owner / assignee

    /** Existing media rows backing every file_id. */
    private const MEDIA_IDS = [1, 2, 3, 4, 5, 6, 7];

    public function run(): void
    {
        DB::transaction(function () {
            $businessId = $this->business();
            $branches = $this->branches($businessId);
            $courses = $this->courses($businessId);
            $levels = $this->levels($courses);
            $lessonPlans = $this->lessonPlans($courses, $levels);
            $planLessons = $this->lessonPlanLessons($lessonPlans);
            $planByCourse = $this->planByCourse($lessonPlans);
            $rooms = $this->rooms($businessId, $branches);
            $teachers = $this->teachers($businessId, $branches);
            $students = $this->students($businessId, $branches, $levels);
            $classes = $this->classes($businessId, $courses, $rooms, $teachers, $planByCourse);
            $this->classStudents($classes, $students);
            $this->enrollments($businessId, $classes, $students);
            $lessons = $this->lessons($classes, $planByCourse, $planLessons);
            $this->materials($courses, $lessonPlans);
            $this->lessonPlanMaterials($planLessons);
            $this->assignments($courses, $levels, $classes, $students, $lessons);
            $this->studentLevels($businessId, $students, $courses, $levels);
            $parents = $this->crm($businessId, $branches, $students);
            $this->accounts($businessId, $branches);

            $this->timetables($courses, $classes, $teachers, $rooms);
            $sessions = $this->sessions($classes);
            $this->attendances($sessions, $students);
            $this->leaveRequests($students, $teachers, $classes, $lessons);
            $this->promotions($businessId, $parents, $students, $classes);
            $this->evaluations($teachers, $students, $parents, $courses, $classes, $lessons);
            $this->wallets($businessId, $parents);
            $this->tasks();
        });

        $this->command?->info('MasterSeeder: demo data seeded.');
    }

    private function now()
    {
        return now();
    }

    /** Timestamps only — for tables without created_by/updated_by columns. */
    private function ts(): array
    {
        return ['created_at' => $this->now(), 'updated_at' => $this->now()];
    }

    /** Timestamps + created_by/updated_by — for the standard audited tables. */
    private function audit(): array
    {
        return [
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
            'created_by' => self::CREATOR,
            'updated_by' => self::CREATOR,
        ];
    }

    private function media(int $i): int
    {
        return self::MEDIA_IDS[$i % count(self::MEDIA_IDS)];
    }

    private function business(): int
    {
        return DB::table('sys_business')->insertGetId([
            'business_code' => 'BIZ001',
            'name' => 'Hana English (Demo)',
            'short_name' => 'Hana',
            'prefix' => 'HN',
            'tax_code' => '0312345678',
            'email' => 'demo@hana.edu.vn',
            'phone' => '02839001234',
            'website' => 'https://hana.edu.vn',
            'logo' => 'assets/upload/business-logo.png',
            'address' => '123 Nguyễn Huệ',
            'province' => 'TP. Hồ Chí Minh',
            'district' => 'Quận 1',
            'ward' => 'Phường Bến Nghé',
            'zip_code' => '700000',
            'manager_id' => self::MANAGER,
            'status' => 'active',
        ] + $this->audit());
    }

    /** @return int[] */
    private function branches(int $businessId): array
    {
        $rows = [
            ['Quận 1', 'CN1', 'Phường Bến Nghé', '700001', true],
            ['Quận 7', 'CN2', 'Phường Tân Phú', '700002', false],
        ];
        $ids = [];
        foreach ($rows as $i => [$area, $code, $ward, $postal, $isMain]) {
            $ids[] = DB::table('sys_branches')->insertGetId([
                'business_id' => $businessId,
                'name' => 'Chi nhánh '.$area,
                'short_name' => $area,
                'code' => $code,
                'phone' => '0283900'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'email' => strtolower($code).'@hana.edu.vn',
                'website' => 'https://hana.edu.vn/'.strtolower($code),
                'address' => $area.', TP.HCM',
                'province' => 'TP. Hồ Chí Minh',
                'district' => $area,
                'ward' => $ward,
                'postal_code' => $postal,
                'manager_id' => self::MANAGER,
                'capacity' => 200 + $i * 50,
                'opened_at' => now()->subYears(2 + $i)->toDateString(),
                'status' => 'active',
                'manager_name' => 'Quản lý '.$area,
                'is_main_branch' => $isMain,
            ] + $this->audit());
        }

        return $ids;
    }

    /** @return array<int, array{id:int,active:bool}> */
    private function courses(int $businessId): array
    {
        $rows = [
            ['Kids English', 'Tiếng Anh trẻ em 4-10 tuổi', true],
            ['IELTS Foundation', 'Nền tảng IELTS cho người mới bắt đầu', true],
            ['TOEIC (tạm dừng)', 'Luyện thi TOEIC — tạm ngừng tuyển sinh', false],
        ];
        $courses = [];
        foreach ($rows as $i => [$name, $desc, $active]) {
            $id = DB::table('edu_courses')->insertGetId([
                'business_id' => $businessId,
                'name' => $name,
                'code' => 'CRS'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'thumbnail' => 'assets/upload/course-'.($i + 1).'.png',
                'duration_minutes' => 90,
                'price_per_lesson' => 200000,
                'description' => $desc,
                'is_active' => $active,
            ] + $this->audit());
            $courses[] = ['id' => $id, 'active' => $active];
        }

        return $courses;
    }

    /** @return array<int, int[]> course_id => level ids (ordered path) */
    private function levels(array $courses): array
    {
        $path = ['Starter', 'Mover', 'Flyer'];
        $cefr = ['Pre-A1', 'A1', 'A2'];
        $byCourse = [];
        foreach ($courses as $course) {
            foreach ($path as $order => $name) {
                $byCourse[$course['id']][] = DB::table('edu_levels')->insertGetId([
                    'level_code' => 'LV'.$course['id'].'-'.($order + 1),
                    'course_id' => $course['id'],
                    'level_name' => $name,
                    'level_order' => $order + 1,
                    'cefr_level' => $cefr[$order],
                    'description' => 'Cấp độ '.$name.' ('.$cefr[$order].')',
                    'status' => 'active',
                ] + $this->ts());
            }
        }

        return $byCourse;
    }

    /**
     * @param  array<int, array{id:int,course_id:int,status:string}>  $lessonPlans
     * @return array<int, array{id:int,course_id:int,status:string}> course_id => chosen plan (prefers published)
     */
    private function planByCourse(array $lessonPlans): array
    {
        $byCourse = [];
        foreach ($lessonPlans as $plan) {
            $courseId = $plan['course_id'];
            if (! isset($byCourse[$courseId]) || $plan['status'] === 'published') {
                $byCourse[$courseId] = $plan;
            }
        }

        return $byCourse;
    }

    /** @return array<int, array{id:int,course_id:int,status:string}> */
    private function lessonPlans(array $courses, array $levels): array
    {
        $statuses = ['draft', 'reviewing', 'published', 'archived'];
        $plans = [];
        foreach ($statuses as $i => $status) {
            $courseId = $courses[$i % count($courses)]['id'];
            $isPublished = $status === 'published';
            $id = DB::table('edu_lesson_plans')->insertGetId([
                'plan_code' => 'LP'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'plan_name' => 'Giáo án '.($i + 1),
                'avatar' => 'assets/upload/plan-'.($i + 1).'.png',
                'course_id' => $courseId,
                'level_id' => $levels[$courseId][0],
                'version' => 1,
                'total_lessons' => 6,
                'description' => 'Giáo án '.($i + 1).' — 6 buổi học',
                'status' => $status,
                'published_at' => $isPublished ? $this->now() : null,
                'published_by' => $isPublished ? self::MANAGER : null,
            ] + $this->audit());
            $plans[] = ['id' => $id, 'course_id' => $courseId, 'status' => $status];
        }

        return $plans;
    }

    /**
     * Lesson templates inside each plan (lesson-plan.md §6, §16). Six per plan so a
     * class's six generated lessons each snapshot a distinct template.
     *
     * @param  array<int, array{id:int,course_id:int,status:string}>  $lessonPlans
     * @return array<int, array<int, array{id:int,snapshot:array<string,mixed>}>> plan_id => templates
     */
    private function lessonPlanLessons(array $lessonPlans): array
    {
        // [title, objective, vocabulary, grammar, activities, homework].
        $templates = [
            ['My Family', 'Giới thiệu thành viên gia đình', 'Father, Mother, Brother, Sister', 'This is my...', 'Flashcard, Speaking', 'Workbook page 10'],
            ['At School', 'Gọi tên đồ dùng học tập', 'Pen, Book, Desk, Bag', 'I have a...', 'Matching, Roleplay', 'Workbook page 14'],
            ['My Body', 'Nhận biết các bộ phận cơ thể', 'Head, Hand, Leg, Eye', 'Touch your...', 'TPR game, Song', 'Draw and label'],
            ['Food & Drink', 'Nói về món ăn yêu thích', 'Rice, Milk, Apple, Water', 'I like...', 'Survey, Speaking', 'Workbook page 20'],
            ['Animals', 'Mô tả các con vật', 'Dog, Cat, Bird, Fish', 'It can...', 'Guessing game', 'Workbook page 24'],
            ['Review & Test', 'Ôn tập và kiểm tra', 'Unit 1-5 vocabulary', 'Mixed structures', 'Quiz, Board game', 'Prepare for test'],
        ];

        $byPlan = [];
        foreach ($lessonPlans as $plan) {
            foreach ($templates as $i => [$title, $objective, $vocabulary, $grammar, $activities, $homework]) {
                $snapshot = [
                    'lesson_no' => $i + 1,
                    'lesson_title' => $title,
                    'objective' => $objective,
                    'vocabulary' => $vocabulary,
                    'grammar' => $grammar,
                    'activities' => $activities,
                    'homework' => $homework,
                    'duration' => 90,
                ];
                $id = DB::table('edu_lesson_plan_lessons')->insertGetId($snapshot + [
                    'lesson_plan_id' => $plan['id'],
                ] + $this->audit());
                $byPlan[$plan['id']][] = ['id' => $id, 'snapshot' => $snapshot];
            }
        }

        return $byPlan;
    }

    /**
     * File attachments per lesson template (edu_lesson_plan_materials). Every template gets
     * one material so the lesson-detail endpoint surfaces materials for generated lessons.
     *
     * @param  array<int, array<int, array{id:int,snapshot:array<string,mixed>}>>  $planLessons
     */
    private function lessonPlanMaterials(array $planLessons): void
    {
        $types = ['pdf', 'video', 'audio', 'slide', 'worksheet', 'homework'];
        $seq = 0;
        foreach ($planLessons as $templates) {
            foreach ($templates as $i => $template) {
                DB::table('edu_lesson_plan_materials')->insert([
                    'lesson_plan_lesson_id' => $template['id'],
                    'file_id' => $this->media($seq++),
                    'material_type' => $types[$i % count($types)],
                ] + $this->audit());
            }
        }
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
                'room_name' => 'Phòng '.($i + 1),
                'avatar' => 'assets/upload/room-'.($i + 1).'.png',
                'room_code' => 'R'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'capacity' => 20 + $i,
                'floor' => 'Tầng '.(($i % 3) + 1),
                'room_type' => $type,
                'status' => $statuses[$i % count($statuses)],
                'description' => 'Phòng '.$type,
            ] + $this->audit());
        }

        return $ids;
    }

    /** @return int[] */
    private function teachers(int $businessId, array $branches): array
    {
        $types = ['full_time', 'part_time', 'freelancer', 'assistant'];
        $statuses = ['active', 'suspended', 'resigned', 'active'];
        $genders = ['female', 'male', 'female', 'male'];
        $ids = [];
        foreach ($types as $i => $type) {
            $status = $statuses[$i];
            $ids[] = DB::table('hr_teachers')->insertGetId([
                'user_id' => $i === 0 ? self::TEACHER_USER : null,
                'business_id' => $businessId,
                'branch_id' => $branches[$i % count($branches)],
                'code' => 'GV'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'full_name' => 'Giáo viên '.($i + 1),
                'avatar' => 'assets/upload/teacher-'.($i + 1).'.png',
                'gender' => $genders[$i],
                'dob' => now()->subYears(30 + $i)->toDateString(),
                'email' => 'gv'.($i + 1).'@hana.edu.vn',
                'phone' => '09220000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'identity_no' => '0790000000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'address' => 'Số '.($i + 1).' đường Sư Vạn Hạnh, TP.HCM',
                'teacher_type' => 'teacher',
                'employment_type' => $type,
                'status' => $status,
                'joined_at' => now()->subYears(3)->subMonths($i)->toDateString(),
                'resigned_at' => $status === 'resigned' ? now()->subMonths(2)->toDateString() : null,
                'note' => 'Giáo viên '.$type,
                'hourly_rate' => 250000 + $i * 25000,
                'monthly_salary' => 15000000 + $i * 1000000,
                'manager_id' => self::MANAGER,
            ] + $this->audit());
        }

        return $ids;
    }

    /** @return int[] */
    private function students(int $businessId, array $branches, array $levels): array
    {
        $statuses = ['active', 'suspended', 'graduated', 'dropped'];
        $genders = ['male', 'female'];
        $sources = ['facebook', 'referral', 'walk_in', 'website'];
        $levelPool = array_merge(...array_values($levels));
        $ids = [];
        foreach ($statuses as $status) {
            // Two students per status for richer linking.
            foreach (range(1, 2) as $n) {
                $seq = count($ids) + 1;
                $ids[] = DB::table('edu_students')->insertGetId([
                    'business_id' => $businessId,
                    'branch_id' => $branches[$seq % count($branches)],
                    'code' => 'STU'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                    'name' => 'Học viên '.$seq,
                    'avatar' => 'assets/upload/student-'.$seq.'.png',
                    'dob' => now()->subYears(8 + $seq)->toDateString(),
                    'gender' => $genders[$seq % count($genders)],
                    'nationality' => 'Việt Nam',
                    'language' => 'vi',
                    'email' => 'hv'.$seq.'@hana.edu.vn',
                    'phone' => '09330000'.str_pad((string) $seq, 2, '0', STR_PAD_LEFT),
                    'level_id' => $levelPool[$seq % count($levelPool)],
                    'status' => $status,
                    'enrollment_date' => now()->subMonths($seq)->toDateString(),
                    'admission_source' => $sources[$seq % count($sources)],
                ] + $this->audit());
            }
        }

        return $ids;
    }

    /**
     * @param  array<int, array{id:int,course_id:int,status:string}>  $planByCourse
     * @return array<int, array{id:int,course_id:int,teacher_id:int,room_id:int}>
     */
    private function classes(int $businessId, array $courses, array $rooms, array $teachers, array $planByCourse): array
    {
        $statuses = ['draft', 'upcoming', 'active', 'suspended', 'completed'];
        $learningTypes = ['scheduled', 'self_learning', 'flexible'];
        $result = [];
        foreach ($statuses as $i => $status) {
            $course = $courses[$i % count($courses)];
            $teacherId = $teachers[$i % count($teachers)];
            $roomId = $rooms[$i % count($rooms)];
            $planId = $planByCourse[$course['id']]['id'] ?? null;
            $id = DB::table('edu_classes')->insertGetId([
                'code' => 'CLS'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'course_id' => $course['id'],
                'lesson_plan_id' => $planId,
                'business_id' => $businessId,
                'assignee_id' => self::SALES,
                'teacher_id' => $teacherId,
                'room_id' => $roomId,
                'name' => 'Lớp '.($i + 1),
                'avatar_url' => 'assets/upload/class-'.($i + 1).'.png',
                'avatar' => 'assets/upload/class-'.($i + 1).'.png',
                'learning_type' => $learningTypes[$i % count($learningTypes)],
                'start_date' => now()->addDays($i * 7)->toDateString(),
                'end_date' => now()->addDays($i * 7 + 90)->toDateString(),
                'max_capacity' => 20,
                'use_course_curriculum' => true,
                'description' => 'Lớp học '.($i + 1).' — '.$course['id'],
                'note' => 'Ghi chú lớp '.($i + 1),
                'min_warning_capacity' => 6,
                'min_capacity' => 4,
                'max_warning_capacity' => 18,
                'status' => $status,
            ] + $this->audit());

            foreach ([2, 4] as $weekday) {
                DB::table('edu_class_schedules')->insert([
                    'class_id' => $id,
                    'weekday' => $weekday,
                    'start_time' => '18:00:00',
                    'end_time' => '19:30:00',
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }

            $result[] = ['id' => $id, 'course_id' => $course['id'], 'teacher_id' => $teacherId, 'room_id' => $roomId];
        }

        return $result;
    }

    private function classStudents(array $classes, array $students): void
    {
        $statuses = ['active', 'reserved', 'completed', 'dropped', 'transferred_out'];
        foreach ($statuses as $i => $status) {
            DB::table('edu_class_students')->insert([
                'class_id' => $classes[$i % count($classes)]['id'],
                'student_id' => $students[$i % count($students)],
                'status' => $status,
                'enrolled_at' => now()->subDays(30 - $i)->toDateString(),
            ] + $this->audit());
        }
    }

    /**
     * @param  array<int, array{id:int,course_id:int,teacher_id:int,room_id:int}>  $classes
     */
    private function enrollments(int $businessId, array $classes, array $students): void
    {
        $statuses = ['pending', 'studying', 'suspended', 'transferred', 'completed', 'cancelled', 'refunded'];
        $pricePerLesson = 200000;
        $totalLessons = 24;
        foreach ($statuses as $i => $status) {
            $class = $classes[$i % count($classes)];
            $completed = $i * 3;
            $tuition = $pricePerLesson * $totalLessons;
            $discount = 100000 * ($i % 3);
            $paid = $status === 'completed' ? $tuition - $discount : ($tuition - $discount) / 2;
            DB::table('edu_enrollments')->insert([
                'code' => 'ENR'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'business_id' => $businessId,
                'student_id' => $students[$i % count($students)],
                'course_id' => $class['course_id'],
                'class_id' => $class['id'],
                'sales_id' => self::SALES,
                'enrolled_at' => now()->subDays(20 - $i)->toDateString(),
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completed,
                'remaining_lessons' => $totalLessons - $completed,
                'price_per_lesson' => $pricePerLesson,
                'tuition_amount' => $tuition,
                'discount_amount' => $discount,
                'paid_amount' => $paid,
                'debt_amount' => $tuition - $discount - $paid,
                'status' => $status,
                'note' => 'Ghi danh '.($i + 1),
                'progress' => $i * 10,
            ] + $this->audit());
        }
    }

    /**
     * Class lessons (buổi học thực tế) generated from a course-matched lesson plan,
     * snapshotting the source template's content (lesson.md §16). One per status variant.
     *
     * @param  array<int, array{id:int,course_id:int,teacher_id:int,room_id:int}>  $classes
     * @param  array<int, array{id:int,course_id:int,status:string}>  $planByCourse
     * @param  array<int, array<int, array{id:int,snapshot:array<string,mixed>}>>  $planLessons
     * @return array<int, int[]> class_id => lesson ids
     */
    private function lessons(array $classes, array $planByCourse, array $planLessons): array
    {
        $statuses = ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'locked'];
        $byClass = [];
        foreach ($classes as $class) {
            $plan = $planByCourse[$class['course_id']] ?? null;
            $planId = $plan['id'] ?? null;
            $templates = $planId ? ($planLessons[$planId] ?? []) : [];

            foreach ($statuses as $i => $status) {
                $template = $templates ? $templates[$i % count($templates)] : null;
                $snapshot = $template['snapshot'] ?? [];
                $isCompleted = in_array($status, ['completed', 'locked'], true);

                $byClass[$class['id']][] = DB::table('edu_lessons')->insertGetId([
                    'class_room_id' => $class['id'],
                    'lesson_plan_id' => $planId,
                    'lesson_plan_lesson_id' => $template['id'] ?? null,
                    'lesson_no' => $i + 1,
                    'lesson_title' => $snapshot['lesson_title'] ?? 'Buổi '.($i + 1),
                    'avatar' => 'assets/upload/lesson-'.($i + 1).'.png',
                    'lesson_date' => now()->addDays($i)->toDateString(),
                    'start_time' => '18:00:00',
                    'end_time' => '19:30:00',
                    'room_id' => $class['room_id'],
                    'teacher_id' => $class['teacher_id'],
                    'objective' => $snapshot['objective'] ?? 'Mục tiêu buổi '.($i + 1),
                    'vocabulary' => $snapshot['vocabulary'] ?? 'Từ vựng buổi '.($i + 1),
                    'grammar' => $snapshot['grammar'] ?? 'Ngữ pháp buổi '.($i + 1),
                    'activities' => $snapshot['activities'] ?? 'Hoạt động buổi '.($i + 1),
                    'homework' => $snapshot['homework'] ?? 'Bài tập buổi '.($i + 1),
                    'lesson_note' => 'Ghi chú buổi '.($i + 1),
                    'status' => $status,
                    'completed_at' => $isCompleted ? $this->now() : null,
                    'locked_at' => $status === 'locked' ? $this->now() : null,
                ] + $this->audit());
            }
        }

        return $byClass;
    }

    private function materials(array $courses, array $lessonPlans): void
    {
        $types = ['pdf', 'video', 'audio', 'slide', 'worksheet', 'homework'];
        $access = ['teacher', 'student', 'parent', 'internal'];
        $statuses = ['draft', 'active'];
        $mimes = [
            'pdf' => 'application/pdf', 'video' => 'video/mp4', 'audio' => 'audio/mpeg',
            'slide' => 'application/vnd.ms-powerpoint', 'worksheet' => 'application/vnd.ms-excel', 'homework' => 'application/msword',
        ];
        foreach ($types as $i => $type) {
            $status = $statuses[$i % count($statuses)];
            $materialId = DB::table('edu_materials')->insertGetId([
                'material_code' => 'MAT'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'material_name' => 'Tài liệu '.($i + 1),
                'material_type' => $type,
                'description' => 'Tài liệu '.$type.' số '.($i + 1),
                'current_version' => 1,
                'access_type' => $access[$i % count($access)],
                'status' => $status,
            ] + $this->audit());

            DB::table('edu_material_versions')->insert([
                'material_id' => $materialId,
                'version' => 1,
                'file_id' => $this->media($i),
                'file_name' => 'tai-lieu-'.($i + 1).'.'.$type,
                'file_size' => 1048576 * ($i + 1),
                'mime_type' => $mimes[$type],
                'change_log' => 'Phiên bản đầu tiên',
                'created_by' => self::CREATOR,
                'created_at' => $this->now(),
            ]);

            // Where-used: link to a course and a lesson plan.
            DB::table('edu_material_mappings')->insert([
                ['material_id' => $materialId, 'entity_type' => 'course', 'entity_id' => $courses[$i % count($courses)]['id']] + $this->audit(),
                ['material_id' => $materialId, 'entity_type' => 'lesson_plan', 'entity_id' => $lessonPlans[$i % count($lessonPlans)]['id']] + $this->audit(),
            ]);
        }
    }

    /**
     * @param  array<int, array{id:int,course_id:int,teacher_id:int,room_id:int}>  $classes
     * @param  array<int, int[]>  $lessons  class_id => lesson ids
     */
    private function assignments(array $courses, array $levels, array $classes, array $students, array $lessons): void
    {
        $types = ['homework', 'worksheet', 'quiz', 'writing', 'speaking', 'listening', 'reading', 'project', 'exam_practice'];
        $statuses = ['draft', 'published', 'closed'];
        $submissionStatuses = ['assigned', 'submitted', 'late_submitted', 'graded', 'reviewed'];
        foreach ($types as $i => $type) {
            $status = $statuses[$i % count($statuses)];
            $class = $classes[$i % count($classes)];
            $classLessons = $lessons[$class['id']] ?? [];
            $assignmentId = DB::table('edu_assignments')->insertGetId([
                'assignment_code' => 'ASG'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'assignment_name' => 'Bài tập '.($i + 1),
                'assignment_type' => $type,
                'avatar' => 'assets/upload/assignment-'.($i + 1).'.png',
                'course_id' => $class['course_id'],
                'level_id' => $levels[$class['course_id']][0],
                'lesson_id' => $classLessons[0] ?? null,
                'class_room_id' => $class['id'],
                'description' => 'Mô tả bài tập '.($i + 1),
                'instruction' => 'Hoàn thành bài tập '.($i + 1),
                'max_score' => 10,
                'due_date' => now()->addDays(7 + $i),
                'allow_late_submission' => true,
                'allow_multiple_submission' => false,
                'status' => $status,
            ] + $this->audit());

            // Only published/closed assignments are handed out.
            if ($status === 'draft') {
                continue;
            }

            foreach ($submissionStatuses as $s => $subStatus) {
                $studentId = $students[($i + $s) % count($students)];
                $submitted = ! in_array($subStatus, ['assigned'], true);
                $graded = in_array($subStatus, ['graded', 'reviewed'], true);
                DB::table('edu_assignment_targets')->insert([
                    'assignment_id' => $assignmentId,
                    'student_id' => $studentId,
                    'assigned_at' => $this->now(),
                ] + $this->ts());
                DB::table('edu_assignment_submissions')->insert([
                    'assignment_id' => $assignmentId,
                    'student_id' => $studentId,
                    'submitted_at' => $submitted ? $this->now() : null,
                    'answer' => $submitted ? 'Bài làm của học viên '.$studentId : null,
                    'score' => $graded ? 8.5 : null,
                    'comment' => $graded ? 'Làm tốt' : null,
                    'result_published' => $graded,
                    'status' => $subStatus,
                ] + $this->audit());
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
                'assigned_by' => self::TEACHER_USER,
                'placement_score' => 30 + $i * 15,
                'status' => 'active',
            ] + $this->audit());

            DB::table('edu_student_level_assessments')->insert([
                'student_id' => $studentId,
                'assessment_type' => 'placement_test',
                'score' => 30 + $i * 15,
                'level_id' => $toLevel,
                'comment' => 'Đánh giá đầu vào',
                'assessed_by' => self::TEACHER_USER,
                'assessed_at' => $this->now(),
            ] + $this->ts());

            DB::table('edu_student_level_histories')->insert([
                'student_level_id' => $studentLevelId,
                'business_id' => $businessId,
                'student_id' => $studentId,
                'from_level_id' => $fromLevel,
                'to_level_id' => $toLevel,
                'source' => $i === 0 ? 'placement' : 'promote',
                'action' => $i === 0 ? 'placement' : 'promote',
                'reason' => $i === 0 ? 'Xếp lớp đầu vào' : 'Đủ điều kiện lên cấp',
                'score' => 30 + $i * 15,
                'note' => 'Lịch sử cấp độ '.($i + 1),
                'created_by' => self::TEACHER_USER,
                'effective_at' => $this->now(),
            ] + $this->ts());
        }
    }

    /** @return int[] parent ids */
    private function crm(int $businessId, array $branches, array $students): array
    {
        $leadStatuses = ['pending', 'verified', 'studying', 'inactive'];
        $sources = ['facebook', 'referral', 'walk_in', 'website'];
        $genders = ['male', 'female'];
        foreach ($leadStatuses as $i => $status) {
            DB::table('crm_leads')->insert([
                'code' => 'LEAD'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'business_id' => $businessId,
                'branch_id' => $branches[$i % count($branches)],
                'name' => 'Tiềm năng '.($i + 1),
                'gender' => $genders[$i % count($genders)],
                'dob' => now()->subYears(6 + $i)->toDateString(),
                'phone' => '09000000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'email' => 'lead'.($i + 1).'@example.com',
                'source' => $sources[$i % count($sources)],
                'owner_id' => self::SALES,
                'status' => $status,
                'note' => 'Ghi chú tiềm năng '.($i + 1),
            ] + $this->audit());
        }

        $parentIds = [];
        $parentStatuses = ['active', 'suspended', 'inactive'];
        foreach ($parentStatuses as $i => $status) {
            $parentIds[] = DB::table('crm_parents')->insertGetId([
                'code' => 'PAR'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'business_id' => $businessId,
                'branch_id' => $branches[$i % count($branches)],
                'name' => 'Phụ huynh '.($i + 1),
                'gender' => $genders[$i % count($genders)],
                'dob' => now()->subYears(35 + $i)->toDateString(),
                'avatar' => 'assets/upload/parent-'.($i + 1).'.png',
                'phone' => '09111111'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'email' => 'ph'.($i + 1).'@example.com',
                'address' => 'Số '.($i + 1).' đường Lê Lợi, TP.HCM',
                'province' => 'TP. Hồ Chí Minh',
                'district' => $i === 0 ? 'Quận 1' : 'Quận 7',
                'occupation' => 'Nhân viên văn phòng',
                'company' => 'Công ty '.($i + 1),
                'note' => 'Ghi chú phụ huynh '.($i + 1),
                'status' => $status,
            ] + $this->audit());
        }

        $relations = ['father', 'mother'];
        foreach ($relations as $i => $relation) {
            DB::table('crm_parent_student')->insert([
                'parent_id' => $parentIds[$i % count($parentIds)],
                'student_id' => $students[$i % count($students)],
                'relation' => $relation,
                'is_primary_contact' => $i === 0,
                'is_billing_contact' => $i === 0,
                'is_pickup_authorized' => true,
                'note' => 'Quan hệ '.$relation,
            ] + $this->audit());
        }

        return $parentIds;
    }

    private function accounts(int $businessId, array $branches): void
    {
        $accounts = [
            ['cash', 'Tiền mặt', null, null],
            ['bank', 'Ngân hàng', 'Vietcombank', '0071000123456'],
            ['ewallet', 'Ví điện tử', 'Momo', '0933000111'],
        ];
        foreach ($accounts as $i => [$type, $name, $bank, $accountNo]) {
            DB::table('fin_accounts')->insert([
                'business_id' => $businessId,
                'branch_id' => $branches[$i % count($branches)],
                'code' => 'ACC-'.strtoupper($type),
                'name' => $name,
                'type' => $type,
                'currency' => 'VND',
                'balance' => 5000000 * ($i + 1),
                'bank_name' => $bank,
                'account_number' => $accountNo,
                'status' => 'active',
                'note' => 'Tài khoản '.$name,
            ] + $this->audit());
        }
    }

    /**
     * Picks one real session per class (from the `timetables()` history, already
     * seeded by this point) and relabels it across the 4 status variants, instead
     * of inserting separate ad-hoc `edu_sessions` rows — those used to duplicate/
     * conflict with the real weekly session history on the same classes.
     *
     * @param  array<int, array{id:int,course_id:int,teacher_id:int,room_id:int}>  $classes
     * @return int[]
     */
    private function sessions(array $classes): array
    {
        $statuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];
        $ids = [];
        foreach (array_slice($classes, 0, count($statuses)) as $i => $class) {
            $sessionId = DB::table('edu_sessions')
                ->where('class_id', $class['id'])
                ->orderBy('session_no')
                ->value('id');
            if (!$sessionId) {
                continue;
            }
            $status = $statuses[$i];
            DB::table('edu_sessions')->where('id', $sessionId)->update([
                'status' => $status,
                'attendance_locked' => $status === 'completed',
                'updated_at' => $this->now(),
            ]);
            $ids[] = $sessionId;
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
        $notes = ['present' => 'Đi học đầy đủ', 'absent' => 'Vắng không phép', 'late' => 'Đi muộn 20 phút', 'excused' => 'Vắng có phép'];
        foreach ($statuses as $i => $status) {
            DB::table('edu_attendances')->insert([
                'session_id' => $sessions[$i % count($sessions)],
                'student_id' => $students[$i % count($students)],
                'status' => $status,
                'checkin_time' => in_array($status, ['present', 'late'], true)
                    ? now()->setTime(18, $status === 'late' ? 20 : 0)
                    : null,
                'checkout_time' => $status === 'present' ? now()->setTime(19, 30) : null,
                'note' => $notes[$status],
            ] + $this->audit());
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
            $classId = $classes[$i % count($classes)]['id'];
            $classLessons = $lessons[$classId] ?? [];
            $lessonId = $classLessons[0] ?? null;
            $isTeacher = $i === 2; // one teacher_leave variant
            $requesterId = $isTeacher ? $teachers[0] : $students[$i % count($students)];
            $isApprovedLike = in_array($status, ['approved', 'completed'], true);

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
                'attachment_file_id' => $this->media($i),
                'status' => $status,
                'approved_by' => $isApprovedLike ? self::MANAGER : null,
                'approved_at' => $isApprovedLike ? $this->now() : null,
                'rejection_reason' => $status === 'rejected' ? 'Không đủ điều kiện' : null,
            ] + $this->audit());

            DB::table('edu_leave_request_logs')->insert([
                'leave_request_id' => $leaveId,
                'action' => 'created',
                'old_status' => null,
                'new_status' => 'pending',
                'note' => 'Tạo đơn nghỉ '.($i + 1),
                'created_by' => self::CREATOR,
            ] + $this->ts());

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
                    ] + $this->audit());
                }
            }
        }
    }

    /**
     * Promotion programmes — one per status variant — plus eligibility rules, reward
     * lines, vouchers (one per status), a usage entry and referrals (one per status)
     * on the active programme.
     *
     * @param  array<int, array{id:int,course_id:int,teacher_id:int,room_id:int}>  $classes
     */
    private function promotions(int $businessId, array $parents, array $students, array $classes): void
    {
        $statuses = ['draft', 'pending', 'active', 'paused', 'expired', 'closed'];
        $types = ['discount', 'voucher', 'gift_lesson', 'wallet_credit', 'combo', 'referral'];

        $promotionIds = [];
        foreach ($statuses as $i => $status) {
            $type = $types[$i % count($types)];
            $isApproved = in_array($status, ['active', 'paused', 'expired', 'closed'], true);
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
                'approved_by' => $isApproved ? self::MANAGER : null,
                'approved_at' => $isApproved ? $this->now() : null,
            ] + $this->audit());
        }

        $activeId = $promotionIds['active'];

        foreach (['min_order' => '5000000', 'new_customer' => '1', 'first_enrollment' => '1', 'course' => '1', 'level' => '1', 'branch' => '1'] as $ruleType => $ruleValue) {
            DB::table('fin_promotion_rules')->insert([
                'promotion_id' => $activeId,
                'rule_type' => $ruleType,
                'rule_value' => $ruleValue,
            ] + $this->ts());
        }

        foreach (['discount' => '10', 'gift_lesson' => '2', 'wallet_credit' => '100000', 'voucher' => '1'] as $rewardType => $rewardValue) {
            DB::table('fin_promotion_rewards')->insert([
                'promotion_id' => $activeId,
                'reward_type' => $rewardType,
                'reward_value' => $rewardValue,
            ] + $this->ts());
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
            ] + $this->audit());
        }

        DB::table('fin_promotion_usages')->insert([
            'promotion_id' => $activeId,
            'voucher_id' => $voucherIds['used'],
            'enrollment_id' => null,
            'invoice_id' => null,
            'customer_id' => $parents[0],
            'discount_amount' => 200000,
            'used_at' => $this->now(),
        ] + $this->ts());

        if (count($parents) >= 2) {
            foreach (['pending', 'rewarded', 'cancelled'] as $i => $rStatus) {
                DB::table('fin_referrals')->insert([
                    'referrer_parent_id' => $parents[0],
                    'referred_parent_id' => $parents[1 + ($i % (count($parents) - 1))],
                    'promotion_id' => $activeId,
                    'reward_amount' => 100000,
                    'status' => $rStatus,
                    'rewarded_at' => $rStatus === 'rewarded' ? $this->now() : null,
                ] + $this->audit());
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
        $classId = $classes[0]['id'];
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
            ['teacher', $teachers[2], 'manager', self::MANAGER, 'quarterly', [3, 3, 2, 3], 'submitted'],

            ['student', $students[0], 'teacher', $teachers[0], 'final', [5, 4, 5, 5], 'approved'],
            ['student', $students[1], 'teacher', $teachers[0], 'midterm', [3, 3, 3, 2], 'draft'],
            ['student', $students[2], 'teacher', $teachers[1], 'lesson', [2, 2, 1, 2], 'rejected'],

            ['parent', $parents[0], 'manager', self::MANAGER, 'monthly', [4, 4, 5, 4], 'approved'],
            ['parent', $parents[1], 'cskh', self::SALES, 'quarterly', [2, 2, 1, 2], 'draft'],
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
                'strengths' => 'Điểm mạnh '.($seq + 1),
                'weaknesses' => 'Điểm cần cải thiện '.($seq + 1),
                'recommendations' => 'Đề xuất '.($seq + 1),
                'status' => $status,
                'evaluated_at' => $this->now(),
            ] + $this->audit());
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
            ] + $this->ts());

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
                    'reference_type' => 'wallet',
                    'reference_id' => $walletId,
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'description' => 'Demo '.$type,
                    'created_by' => self::CREATOR,
                ] + $this->ts());
            }

            DB::table('fin_wallet_adjustments')->insert([
                'wallet_id' => $walletId,
                'adjustment_type' => 'increase',
                'amount' => 25000,
                'reason' => 'Bù trừ đối soát',
                'approved_by' => self::MANAGER,
            ] + $this->ts());
        }
    }

    /**
     * Tasks — one per status variant; the first carries a checklist, comment and attachment.
     */
    private function tasks(): void
    {
        $statuses = ['draft', 'open', 'in_progress', 'pending_review', 'completed', 'rejected', 'cancelled', 'overdue'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $categories = ['general', 'academic', 'hr', 'finance', 'sales', 'operation'];
        $progress = ['draft' => 0, 'open' => 0, 'in_progress' => 50, 'pending_review' => 100, 'completed' => 100, 'rejected' => 60, 'cancelled' => 0, 'overdue' => 30];

        $firstTaskId = null;
        foreach ($statuses as $i => $status) {
            $id = DB::table('task_tasks')->insertGetId([
                'task_code' => 'TASK'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'title' => 'Công việc '.($i + 1),
                'description' => 'Mô tả công việc '.($i + 1),
                'category' => $categories[$i % count($categories)],
                'priority' => $priorities[$i % count($priorities)],
                'status' => $status,
                'progress' => $progress[$status],
                'start_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'completed_at' => $status === 'completed' ? $this->now() : null,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
            $firstTaskId ??= $id;
        }

        DB::table('task_checklists')->insert([
            ['task_id' => $firstTaskId, 'title' => 'Chuẩn bị tài liệu', 'is_completed' => true, 'completed_at' => $this->now(), 'created_at' => $this->now(), 'updated_at' => $this->now()],
            ['task_id' => $firstTaskId, 'title' => 'Gửi cho phụ huynh', 'is_completed' => false, 'completed_at' => null, 'created_at' => $this->now(), 'updated_at' => $this->now()],
        ]);

        DB::table('task_comments')->insert([
            'task_id' => $firstTaskId,
            'comment' => 'Đã bắt đầu xử lý công việc.',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        $mediaId = DB::table('media')->insertGetId([
            'file_path' => 'storage/uploads/task-demo.pdf',
            'file_name' => 'task-demo.pdf',
            'file_type' => 'application/pdf',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        DB::table('task_attachments')->insert([
            'task_id' => $firstTaskId,
            'file_id' => $mediaId,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    /**
     * Timetables — every class gets one (status cycles through all 4 variants for
     * demo coverage), each with weekly rules (Mon/Wed, matching the `edu_class_schedules`
     * rows seeded in `classes()`) and a full `edu_sessions` history spanning ~30 days in
     * the past through ~60 days ahead, so `/edu/timetable/calendar` has real data for
     * every class instead of only the one status='active' row.
     */
    private function timetables(array $courses, array $classes, array $teachers, array $rooms): void
    {
        $statuses = ['draft', 'active', 'completed', 'cancelled'];

        foreach ($classes as $i => $class) {
            $classId = $class['id'];
            $teacherId = $teachers[$i % count($teachers)];
            $roomId = $rooms[$i % count($rooms)];
            $courseId = $courses[$i % count($courses)]['id'];
            $status = $statuses[$i % count($statuses)];

            $start = now()->subDays(30)->startOfWeek();
            $end = now()->addDays(60);

            $timetableId = DB::table('edu_timetables')->insertGetId([
                'timetable_code' => 'TKB'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'name' => 'Thời khóa biểu '.($i + 1),
                'course_id' => $courseId,
                'class_room_id' => $classId,
                'teacher_id' => $teacherId,
                'room_id' => $roomId,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'schedule_pattern' => 'fixed_weekly',
                'total_sessions' => 0,
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            foreach ([1, 3] as $dayOfWeek) {
                DB::table('edu_timetable_rules')->insert([
                    'timetable_id' => $timetableId,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => '18:00:00',
                    'end_time' => '19:30:00',
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }

            $no = 0;
            $date = $start->copy();
            $today = now()->startOfDay();
            while ($date->lte($end)) {
                if (in_array($date->dayOfWeekIso, [1, 3], true)) {
                    $no++;
                    DB::table('edu_sessions')->insert([
                        'class_id' => $classId,
                        'timetable_id' => $timetableId,
                        'session_no' => $no,
                        'name' => 'Buổi '.$no,
                        'session_date' => $date->toDateString(),
                        'start_time' => '18:00:00',
                        'end_time' => '19:30:00',
                        'teacher_id' => $teacherId,
                        'room_id' => $roomId,
                        'status' => $date->lt($today) ? 'completed' : 'upcoming',
                        'created_at' => $this->now(),
                        'updated_at' => $this->now(),
                    ]);
                }
                $date->addDay();
            }

            DB::table('edu_timetables')->where('id', $timetableId)->update(['total_sessions' => $no]);
        }
    }
}
