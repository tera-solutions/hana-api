<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds one coherent demo centre that walks the whole business workflow:
 *
 *   Lead → Parent → Student → Enrollment → Invoice → Payment / Debt / Refund
 *   → Class → Timetable/Sessions → Lessons → Attendance → Homework → Exam
 *   → Evaluation → Level promotion → Leave requests → Wallet
 *
 * Every entity is linked to the same cast of characters so each portal has a
 * working, self-consistent view:
 *
 *   Teacher portal:  giaovien  / 12345678  (Cô Hà — GV001, chủ nhiệm lớp Kids A1)
 *                    giaovien2 / 12345678  (Thầy Minh — GV002, trợ giảng + lớp IELTS)
 *   Parent portal:   phuhuynh1 / 12345678  (Chị Lan — 2 con: Bé An, Bé Bình)
 *                    phuhuynh2 / 12345678  (Anh Tuấn — 1 con: Bé Chi)
 *   Student portal:  hocvien1..hocvien4 / 12345678 (Bé An, Bình, Chi, Dũng)
 *   Lesson/Admin:    super / admin (BusinessAndUserSeeder, is_admin — unscoped)
 *
 * Portal users are deliberately NOT admins, so TeacherScope actually scopes them.
 *
 * Runs after BusinessAndUserSeeder + permission seeders (reuses the first
 * business and the TEACHER_ROLE / STUDENT_ROLE / PARENT_ROLE roles). Standalone:
 *   php artisan db:seed --class="Database\Seeders\DemoDataSeeder"
 */
class DemoDataSeeder extends Seeder
{
    private const PASSWORD = '12345678';

    private int $businessId;

    private int $adminId;

    private int $teacherRoleId;

    private int $studentRoleId;

    private int $parentRoleId;

    /** @var int[] */
    private array $media = [];

    public function run(): void
    {
        $this->businessId = (int) DB::table('sys_business')->orderBy('id')->value('id');
        $this->adminId = (int) DB::table('users')->where('is_admin', true)->orderBy('id')->value('id');

        if (! $this->businessId || ! $this->adminId) {
            $this->command?->error('DemoDataSeeder: run BusinessAndUserSeeder first.');

            return;
        }

        $fallbackRoleId = (int) DB::table('sys_roles')->orderBy('id')->value('id');
        $roleId = fn (string $code): int => (int) (DB::table('sys_roles')->where('code', $code)->value('id') ?: $fallbackRoleId);

        $this->teacherRoleId = $roleId('TEACHER_ROLE');
        $this->studentRoleId = $roleId('STUDENT_ROLE');
        $this->parentRoleId = $roleId('PARENT_ROLE');

        DB::transaction(function () {
            $this->media = $this->mediaAssets();
            $branches = $this->branches();
            $courses = $this->courses();                            // [kids, ielts]
            $levels = $this->levels($courses);                      // course_id => [level ids by order]
            $plans = $this->lessonPlans($courses, $levels);         // course_id => plan id
            $templates = $this->lessonPlanTemplates($plans);        // plan id => templates
            $this->materials($courses, $plans);
            $rooms = $this->rooms($branches);
            $teachers = $this->teachers($branches);                 // [GV001, GV002, GV003]
            $this->questionBank($levels[$courses[0]]);
            $exams = $this->exams($courses[0], $levels[$courses[0]]); // [placement, final]

            $students = $this->students($branches, $levels[$courses[0]]); // 6 ids
            $parents = $this->crm($branches, $students);
            $this->studentLevels($students, $courses[0], $levels[$courses[0]]);

            $classes = $this->classes($courses, $plans, $rooms, $teachers);
            $this->classRoster($classes, $students);
            $this->timetables($classes, $teachers, $rooms);
            $lessons = $this->lessons($classes, $plans, $templates, $teachers, $rooms);
            $this->attendance($classes, $students);
            $this->enrollmentsAndBilling($classes, $courses, $students, $parents, $branches);
            $this->assignments($classes, $courses, $levels, $students, $lessons);
            $this->examSession($exams[1], $classes[0], $rooms[0], $teachers[0], $students, $courses[0], $levels[$courses[0]]);
            $this->evaluations($teachers, $students, $parents, $courses[0], $classes[0], $lessons);
            $this->leaveRequests($students, $teachers, $classes[0], $lessons);
            $this->wallets($parents);
            $this->promotions($parents);
            $this->tasks();
        });

        $this->command?->info('DemoDataSeeder: demo workflow seeded.');
        $this->command?->table(
            ['Portal', 'Username', 'Password'],
            [
                ['Teacher (chủ nhiệm)', 'giaovien', self::PASSWORD],
                ['Teacher (trợ giảng)', 'giaovien2', self::PASSWORD],
                ['Parent (2 con)', 'phuhuynh1', self::PASSWORD],
                ['Parent (1 con)', 'phuhuynh2', self::PASSWORD],
                ['Student', 'hocvien1..hocvien4', self::PASSWORD],
            ],
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function ts(): array
    {
        return ['created_at' => now(), 'updated_at' => now()];
    }

    private function audit(): array
    {
        return $this->ts() + ['created_by' => $this->adminId, 'updated_by' => $this->adminId];
    }

    private function file(int $i): int
    {
        return $this->media[$i % count($this->media)];
    }

    /** A portal login (non-admin) so scoping and per-role permissions apply. */
    private function portalUser(string $username, string $fullName, string $email, int $roleId): int
    {
        return DB::table('users')->insertGetId([
            'full_name' => $fullName,
            'avatar' => 'https://api.anhnguhana.com/assets/avatar/01.png',
            'username' => $username,
            'email' => $email,
            'status' => 'active',
            'code' => 'U_'.strtoupper($username),
            'is_active' => true,
            'is_admin' => false,
            'password' => Hash::make(self::PASSWORD),
            'business_id' => $this->businessId,
            'role_id' => $roleId,
        ] + $this->ts());
    }

    // ── Foundation ───────────────────────────────────────────────────────────

    /** @return int[] */
    private function mediaAssets(): array
    {
        $ids = [];
        foreach (range(1, 8) as $i) {
            $ids[] = DB::table('media')->insertGetId([
                'file_path' => 'assets/demo/file-'.$i.'.pdf',
                'file_name' => 'demo-file-'.$i.'.pdf',
                'file_type' => 'application/pdf',
                'file_size' => 524288 * $i,
                'title' => 'Tài liệu demo '.$i,
                'uploaded_by' => $this->adminId,
                'created_by' => $this->adminId,
            ] + $this->ts());
        }

        return $ids;
    }

    /** @return int[] */
    private function branches(): array
    {
        $rows = [
            ['Quận 1', 'CN1', 'Phường Bến Nghé', true],
            ['Quận 7', 'CN2', 'Phường Tân Phú', false],
        ];
        $ids = [];
        foreach ($rows as $i => [$area, $code, $ward, $isMain]) {
            $ids[] = DB::table('sys_branches')->insertGetId([
                'business_id' => $this->businessId,
                'name' => 'Chi nhánh '.$area,
                'short_name' => $area,
                'code' => $code,
                'phone' => '0283900'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'email' => strtolower($code).'@hana.edu.vn',
                'address' => $area.', TP.HCM',
                'province' => 'TP. Hồ Chí Minh',
                'district' => $area,
                'ward' => $ward,
                'manager_id' => $this->adminId,
                'capacity' => 200,
                'opened_at' => now()->subYears(2)->toDateString(),
                'status' => 'active',
                'manager_name' => 'Quản lý '.$area,
                'is_main_branch' => $isMain,
            ] + $this->audit());
        }

        return $ids;
    }

    /** @return int[] [kids, ielts] */
    private function courses(): array
    {
        $rows = [
            ['Kids English', 'KIDS', 'Tiếng Anh trẻ em 4-10 tuổi', 200000],
            ['IELTS Foundation', 'IELTS', 'Nền tảng IELTS cho học viên lớn', 250000],
        ];
        $ids = [];
        foreach ($rows as $i => [$name, $code, $desc, $price]) {
            $ids[] = DB::table('edu_courses')->insertGetId([
                'business_id' => $this->businessId,
                'name' => $name,
                'code' => 'CRS-'.$code,
                'thumbnail' => 'assets/demo/course-'.($i + 1).'.png',
                'duration_minutes' => 90,
                'price_per_lesson' => $price,
                'description' => $desc,
                'is_active' => true,
            ] + $this->audit());
        }

        return $ids;
    }

    /** @return array<int, int[]> course_id => ordered level ids */
    private function levels(array $courses): array
    {
        $paths = [
            $courses[0] => [['Starter', 'Pre-A1'], ['Mover', 'A1'], ['Flyer', 'A2']],
            $courses[1] => [['Pre-IELTS', 'A2'], ['IELTS 4.0', 'B1'], ['IELTS 5.5', 'B2']],
        ];
        $byCourse = [];
        foreach ($paths as $courseId => $path) {
            foreach ($path as $order => [$name, $cefr]) {
                $byCourse[$courseId][] = DB::table('edu_levels')->insertGetId([
                    'level_code' => 'LV'.$courseId.'-'.($order + 1),
                    'course_id' => $courseId,
                    'level_name' => $name,
                    'level_order' => $order + 1,
                    'cefr_level' => $cefr,
                    'description' => 'Cấp độ '.$name.' ('.$cefr.')',
                    'status' => 'active',
                ] + $this->ts());
            }
        }

        return $byCourse;
    }

    /** @return array<int, int> course_id => published plan id */
    private function lessonPlans(array $courses, array $levels): array
    {
        $plans = [];
        foreach ($courses as $i => $courseId) {
            $plans[$courseId] = DB::table('edu_lesson_plans')->insertGetId([
                'plan_code' => 'LP'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'plan_name' => 'Giáo án '.($i === 0 ? 'Kids Starter' : 'Pre-IELTS'),
                'avatar' => 'assets/demo/plan-'.($i + 1).'.png',
                'course_id' => $courseId,
                'level_id' => $levels[$courseId][0],
                'version' => 1,
                'total_lessons' => 8,
                'description' => 'Giáo án 8 buổi — đã xuất bản, gắn vào lớp học',
                'status' => 'published',
                'published_at' => now()->subMonths(2),
                'published_by' => $this->adminId,
            ] + $this->audit());
        }

        return $plans;
    }

    /**
     * @return array<int, array<int, array{id:int,snapshot:array<string,mixed>}>> plan id => templates
     */
    private function lessonPlanTemplates(array $plans): array
    {
        $units = [
            ['My Family', 'Giới thiệu thành viên gia đình', 'Father, Mother, Brother, Sister', 'This is my...', 'Flashcard, Speaking', 'Workbook page 10'],
            ['At School', 'Gọi tên đồ dùng học tập', 'Pen, Book, Desk, Bag', 'I have a...', 'Matching, Roleplay', 'Workbook page 14'],
            ['My Body', 'Nhận biết các bộ phận cơ thể', 'Head, Hand, Leg, Eye', 'Touch your...', 'TPR game, Song', 'Draw and label'],
            ['Food & Drink', 'Nói về món ăn yêu thích', 'Rice, Milk, Apple, Water', 'I like...', 'Survey, Speaking', 'Workbook page 20'],
            ['Animals', 'Mô tả các con vật', 'Dog, Cat, Bird, Fish', 'It can...', 'Guessing game', 'Workbook page 24'],
            ['Weather', 'Nói về thời tiết', 'Sunny, Rainy, Hot, Cold', 'It is...', 'Weather chart', 'Workbook page 28'],
            ['My Town', 'Mô tả nơi sinh sống', 'Park, School, Shop, House', 'There is a...', 'Map activity', 'Workbook page 32'],
            ['Review & Test', 'Ôn tập và kiểm tra', 'Unit 1-7 vocabulary', 'Mixed structures', 'Quiz, Board game', 'Prepare for test'],
        ];

        $types = ['pdf', 'video', 'audio', 'slide', 'worksheet', 'homework'];
        $byPlan = [];
        $seq = 0;
        foreach ($plans as $planId) {
            foreach ($units as $i => [$title, $objective, $vocabulary, $grammar, $activities, $homework]) {
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
                    'lesson_plan_id' => $planId,
                ] + $this->audit());

                DB::table('edu_lesson_plan_materials')->insert([
                    'lesson_plan_lesson_id' => $id,
                    'file_id' => $this->file($seq),
                    'material_type' => $types[$seq++ % count($types)],
                ] + $this->audit());

                $byPlan[$planId][] = ['id' => $id, 'snapshot' => $snapshot];
            }
        }

        return $byPlan;
    }

    /**
     * Learning-resource library (`edu_materials`), linked via `edu_material_mappings`
     * to the Kids course and its lesson plan — surfaced by the teacher app's
     * classroom "Tài liệu" tab (`/edu/material/list?entity_type=...`).
     *
     * @param  array<int, int>  $plans  course_id => plan id
     */
    private function materials(array $courses, array $plans): void
    {
        $categoryId = DB::table('edu_material_categories')->insertGetId([
            'category_code' => 'CAT001',
            'category_name' => 'Giáo trình & tài liệu lớp học',
        ] + $this->audit());

        $kidsCourseId = $courses[0];
        $kidsPlanId = $plans[$kidsCourseId];

        // [name, type, access_type, status, entity_type, entity_id, file].
        $rows = [
            ['Sách giáo trình Kids Starter', 'pdf', 'student', 'active', 'course', $kidsCourseId, true],
            ['Video bài giảng Unit 1 - My Family', 'video', 'student', 'active', 'lesson_plan', $kidsPlanId, true],
            ['Worksheet ôn tập giữa khóa', 'worksheet', 'teacher', 'draft', 'course', $kidsCourseId, false],
        ];

        foreach ($rows as $i => [$name, $type, $access, $status, $entityType, $entityId, $hasFile]) {
            $materialId = DB::table('edu_materials')->insertGetId([
                'material_code' => 'MAT'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'material_name' => $name,
                'material_type' => $type,
                'category_id' => $categoryId,
                'description' => $name,
                'current_version' => $hasFile ? 1 : 0,
                'access_type' => $access,
                'status' => $status,
            ] + $this->audit());

            if ($hasFile) {
                DB::table('edu_material_versions')->insert([
                    'material_id' => $materialId,
                    'version' => 1,
                    'file_id' => $this->file($i),
                    'file_name' => strtolower(str_replace(' ', '-', $name)).'.'.($type === 'video' ? 'mp4' : 'pdf'),
                    'file_size' => 1048576 * ($i + 2),
                    'mime_type' => $type === 'video' ? 'video/mp4' : 'application/pdf',
                    'change_log' => 'Phiên bản đầu tiên',
                    'created_by' => $this->adminId,
                    'created_at' => now(),
                ]);
            }

            DB::table('edu_material_mappings')->insert([
                'material_id' => $materialId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ] + $this->audit());
        }
    }

    /** @return int[] */
    private function rooms(array $branches): array
    {
        $rows = [
            ['Phòng 101', 'classroom'],
            ['Phòng 102', 'classroom'],
            ['Phòng Speaking', 'speaking_room'],
        ];
        $ids = [];
        foreach ($rows as $i => [$name, $type]) {
            $ids[] = DB::table('edu_rooms')->insertGetId([
                'business_id' => $this->businessId,
                'branch_id' => $branches[$i % count($branches)],
                'room_name' => $name,
                'avatar' => 'assets/demo/room-'.($i + 1).'.png',
                'room_code' => 'R'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'capacity' => 20,
                'floor' => 'Tầng '.(($i % 2) + 1),
                'room_type' => $type,
                'status' => 'active',
                'description' => $name,
            ] + $this->audit());
        }

        return $ids;
    }

    /** @return int[] [GV001 Cô Hà (user giaovien), GV002 Thầy Minh (user giaovien2), GV003] */
    private function teachers(array $branches): array
    {
        $rows = [
            ['Cô Hà', 'giaovien', 'gv.ha@hana.edu.vn', 'female', 'full_time'],
            ['Thầy Minh', 'giaovien2', 'gv.minh@hana.edu.vn', 'male', 'part_time'],
            ['Cô Trang', null, 'gv.trang@hana.edu.vn', 'female', 'full_time'],
        ];
        $ids = [];
        foreach ($rows as $i => [$name, $username, $email, $gender, $employment]) {
            $userId = $username ? $this->portalUser($username, $name, $email, $this->teacherRoleId) : null;

            $ids[] = DB::table('hr_teachers')->insertGetId([
                'user_id' => $userId,
                'business_id' => $this->businessId,
                'branch_id' => $branches[$i % count($branches)],
                'code' => 'GV'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'full_name' => $name,
                'avatar' => 'assets/demo/teacher-'.($i + 1).'.png',
                'gender' => $gender,
                'dob' => now()->subYears(28 + $i)->toDateString(),
                'email' => $email,
                'phone' => '09220000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'identity_no' => '0790000000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'address' => 'Số '.($i + 1).' đường Sư Vạn Hạnh, TP.HCM',
                'teacher_type' => 'teacher',
                'employment_type' => $employment,
                'status' => 'active',
                'joined_at' => now()->subYears(2)->subMonths($i)->toDateString(),
                'hourly_rate' => 250000 + $i * 25000,
                'monthly_salary' => 15000000 + $i * 1000000,
                'manager_id' => $this->adminId,
            ] + $this->audit());
        }

        return $ids;
    }

    private function questionBank(array $kidsLevels): void
    {
        $grammar = DB::table('edu_question_categories')->insertGetId([
            'category_code' => 'QC001',
            'category_name' => 'Grammar',
        ] + $this->audit());
        $vocab = DB::table('edu_question_categories')->insertGetId([
            'category_code' => 'QC002',
            'category_name' => 'Vocabulary',
        ] + $this->audit());

        $rows = [
            ['single_choice', 'grammar', 'easy', $grammar, 'Choose the correct form: This ___ my father.'],
            ['single_choice', 'grammar', 'medium', $grammar, 'Choose the correct form: She ___ two brothers.'],
            ['fill_blank', 'vocabulary', 'easy', $vocab, 'Complete: I write with a ___.'],
            ['single_choice', 'vocabulary', 'medium', $vocab, 'Which word is a body part?'],
            ['multiple_choice', 'vocabulary', 'medium', $vocab, 'Select all the animals.'],
            ['fill_blank', 'grammar', 'hard', $grammar, 'Complete: A bird ___ fly.'],
        ];
        foreach ($rows as $i => [$type, $skill, $difficulty, $categoryId, $content]) {
            DB::table('edu_questions')->insert([
                'question_code' => 'Q'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'question_type' => $type,
                'skill' => $skill,
                'difficulty' => $difficulty,
                'level_id' => $kidsLevels[$i % count($kidsLevels)],
                'category_id' => $categoryId,
                'content' => $content,
                'score' => 1,
                'explanation' => 'Giải thích câu '.($i + 1),
                'version' => 1,
                'status' => 'active',
            ] + $this->audit());
        }
    }

    /** @return int[] [placement exam id, final exam id] */
    private function exams(int $kidsCourseId, array $kidsLevels): array
    {
        $rows = [
            ['EXM001', 'Placement Test — Kids', 'placement', null, 45, 100, 50],
            ['EXM002', 'Kids Starter — Final Exam', 'final', $kidsLevels[0], 60, 100, 60],
        ];
        $ids = [];
        foreach ($rows as [$code, $name, $type, $levelId, $duration, $total, $passing]) {
            $examId = DB::table('edu_exams')->insertGetId([
                'exam_code' => $code,
                'exam_name' => $name,
                'exam_type' => $type,
                'course_id' => $kidsCourseId,
                'level_id' => $levelId,
                'duration' => $duration,
                'total_score' => $total,
                'passing_score' => $passing,
                'version' => 1,
                'status' => 'active',
            ] + $this->audit());

            foreach (['listening', 'reading', 'grammar', 'vocabulary'] as $i => $skill) {
                DB::table('edu_exam_questions')->insert([
                    'exam_id' => $examId,
                    'skill' => $skill,
                    'question_type' => 'single_choice',
                    'content' => ucfirst($skill).' question '.($i + 1),
                    'answer_key' => json_encode(['correct' => 'A']),
                    'score' => 25,
                    'difficulty' => 'medium',
                ] + $this->audit());
            }

            $ids[] = $examId;
        }

        return $ids;
    }

    // ── People (CRM → Student) ───────────────────────────────────────────────

    /** @return int[] 6 students: 4 active (portal users), 1 graduated, 1 dropped */
    private function students(array $branches, array $kidsLevels): array
    {
        $rows = [
            ['Bé An', 'hocvien1', 'active'],
            ['Bé Bình', 'hocvien2', 'active'],
            ['Bé Chi', 'hocvien3', 'active'],
            ['Bé Dũng', 'hocvien4', 'active'],
            ['Bé Em', null, 'graduated'],
            ['Bé Phúc', null, 'dropped'],
        ];
        $sources = ['facebook', 'referral', 'walk_in', 'website'];
        $ids = [];
        foreach ($rows as $i => [$name, $username, $status]) {
            $email = 'hv'.($i + 1).'@hana.edu.vn';
            $userId = $username ? $this->portalUser($username, $name, $email, $this->studentRoleId) : null;

            $studentId = DB::table('edu_students')->insertGetId([
                'user_id' => $userId,
                'business_id' => $this->businessId,
                'branch_id' => $branches[$i % count($branches)],
                'code' => 'STU'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'name' => $name,
                'avatar' => 'assets/demo/student-'.($i + 1).'.png',
                'dob' => now()->subYears(7 + $i)->toDateString(),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'nationality' => 'Việt Nam',
                'language' => 'vi',
                'email' => $email,
                'phone' => '09330000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'level_id' => $kidsLevels[0],
                'status' => $status,
                'enrollment_date' => now()->subMonths(2 + $i)->toDateString(),
                'admission_source' => $sources[$i % count($sources)],
            ] + $this->audit());

            DB::table('edu_student_profiles')->insert([
                'student_id' => $studentId,
                'school' => 'Tiểu học Nguyễn Bỉnh Khiêm',
                'grade' => 'Lớp '.(($i % 5) + 1),
                'address' => 'Số '.($i + 1).' đường Lê Lợi, TP.HCM',
                'note' => 'Hồ sơ học viên '.($i + 1),
            ] + $this->ts());

            $ids[] = $studentId;
        }

        return $ids;
    }

    /**
     * Leads (two of them converted), parents with portal logins, and the
     * parent-student relationships behind the parent portal.
     *
     * @return int[] [Chị Lan, Anh Tuấn, Cô Mai]
     */
    private function crm(array $branches, array $students): array
    {
        $leadRows = [
            ['Tiềm năng mới', 'pending'],
            ['Đã xác minh', 'verified'],
            ['Chị Lan (đã ghi danh)', 'studying'],
            ['Anh Tuấn (đã ghi danh)', 'studying'],
            ['Không phản hồi', 'inactive'],
        ];
        $sources = ['facebook', 'referral', 'walk_in', 'website'];
        foreach ($leadRows as $i => [$name, $status]) {
            DB::table('crm_leads')->insert([
                'code' => 'LEAD'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'business_id' => $this->businessId,
                'branch_id' => $branches[$i % count($branches)],
                'name' => $name,
                'gender' => $i % 2 === 0 ? 'female' : 'male',
                'dob' => now()->subYears(30 + $i)->toDateString(),
                'phone' => '09000000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'email' => 'lead'.($i + 1).'@example.com',
                'source' => $sources[$i % count($sources)],
                'owner_id' => $this->adminId,
                'status' => $status,
                'note' => 'Ghi chú tiềm năng '.($i + 1),
            ] + $this->audit());
        }

        $parentRows = [
            ['Chị Lan', 'phuhuynh1', 'ph.lan@example.com', 'female'],
            ['Anh Tuấn', 'phuhuynh2', 'ph.tuan@example.com', 'male'],
            ['Cô Mai', null, 'ph.mai@example.com', 'female'],
        ];
        $parentIds = [];
        foreach ($parentRows as $i => [$name, $username, $email, $gender]) {
            $userId = $username ? $this->portalUser($username, $name, $email, $this->parentRoleId) : null;

            $parentIds[] = DB::table('crm_parents')->insertGetId([
                'user_id' => $userId,
                'code' => 'PAR'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'business_id' => $this->businessId,
                'branch_id' => $branches[$i % count($branches)],
                'name' => $name,
                'gender' => $gender,
                'dob' => now()->subYears(35 + $i)->toDateString(),
                'avatar' => 'assets/demo/parent-'.($i + 1).'.png',
                'phone' => '09111111'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'email' => $email,
                'address' => 'Số '.($i + 1).' đường Lê Lợi, TP.HCM',
                'province' => 'TP. Hồ Chí Minh',
                'district' => $i === 0 ? 'Quận 1' : 'Quận 7',
                'occupation' => 'Nhân viên văn phòng',
                'company' => 'Công ty '.($i + 1),
                'status' => 'active',
            ] + $this->audit());
        }

        // Chị Lan → An + Bình; Anh Tuấn → Chi; Cô Mai → Dũng.
        $links = [
            [$parentIds[0], $students[0], 'mother', true],
            [$parentIds[0], $students[1], 'mother', false],
            [$parentIds[1], $students[2], 'father', true],
            [$parentIds[2], $students[3], 'mother', true],
        ];
        foreach ($links as [$parentId, $studentId, $relation, $isPrimary]) {
            DB::table('crm_parent_student')->insert([
                'parent_id' => $parentId,
                'student_id' => $studentId,
                'relation' => $relation,
                'is_primary_contact' => $isPrimary,
                'is_billing_contact' => $isPrimary,
                'is_pickup_authorized' => true,
                'note' => 'Quan hệ '.$relation,
            ] + $this->audit());
        }

        return $parentIds;
    }

    private function studentLevels(array $students, int $kidsCourseId, array $kidsLevels): void
    {
        foreach (array_slice($students, 0, 4) as $i => $studentId) {
            $studentLevelId = DB::table('edu_student_levels')->insertGetId([
                'business_id' => $this->businessId,
                'student_id' => $studentId,
                'course_id' => $kidsCourseId,
                'level_id' => $kidsLevels[0],
                'assigned_at' => now()->subMonths(2),
                'assigned_by' => $this->adminId,
                'placement_score' => 40 + $i * 10,
                'status' => 'active',
            ] + $this->audit());

            DB::table('edu_student_level_assessments')->insert([
                'student_id' => $studentId,
                'assessment_type' => 'placement_test',
                'score' => 40 + $i * 10,
                'level_id' => $kidsLevels[0],
                'comment' => 'Đánh giá đầu vào',
                'assessed_by' => $this->adminId,
                'assessed_at' => now()->subMonths(2),
            ] + $this->ts());

            DB::table('edu_student_level_histories')->insert([
                'student_level_id' => $studentLevelId,
                'business_id' => $this->businessId,
                'student_id' => $studentId,
                'from_level_id' => null,
                'to_level_id' => $kidsLevels[0],
                'source' => 'placement',
                'action' => 'placement',
                'reason' => 'Xếp lớp đầu vào',
                'score' => 40 + $i * 10,
                'created_by' => $this->adminId,
                'effective_at' => now()->subMonths(2),
            ] + $this->ts());
        }
    }

    // ── Classes & scheduling ─────────────────────────────────────────────────

    /**
     * CLS001 Kids A1 (active, GV001 + GV002 trợ giảng), CLS002 IELTS (upcoming,
     * GV002), CLS003 Kids (completed, GV001).
     *
     * @return array<int, array{id:int,course_id:int,teacher_id:int,room_id:int,plan_id:int,status:string}>
     */
    private function classes(array $courses, array $plans, array $rooms, array $teachers): array
    {
        $rows = [
            ['Kids A1 — Tối T2/T4', $courses[0], $teachers[0], $rooms[0], 'active', -30, 60, [1, 3]],
            ['IELTS Foundation — Tối T3/T5', $courses[1], $teachers[1], $rooms[1], 'upcoming', 7, 97, [2, 4]],
            ['Kids A1 — Khóa đã kết thúc', $courses[0], $teachers[0], $rooms[0], 'completed', -180, -30, [1, 3]],
        ];
        $result = [];
        foreach ($rows as $i => [$name, $courseId, $teacherId, $roomId, $status, $startOffset, $endOffset, $weekdays]) {
            $id = DB::table('edu_classes')->insertGetId([
                'code' => 'CLS'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'course_id' => $courseId,
                'lesson_plan_id' => $plans[$courseId],
                'business_id' => $this->businessId,
                'assignee_id' => $this->adminId,
                'teacher_id' => $teacherId,
                'room_id' => $roomId,
                'name' => $name,
                'avatar_url' => 'assets/demo/class-'.($i + 1).'.png',
                'avatar' => 'assets/demo/class-'.($i + 1).'.png',
                'learning_type' => 'scheduled',
                'start_date' => now()->addDays($startOffset)->toDateString(),
                'end_date' => now()->addDays($endOffset)->toDateString(),
                'max_capacity' => 20,
                'use_course_curriculum' => true,
                'description' => $name,
                'min_warning_capacity' => 6,
                'min_capacity' => 4,
                'max_warning_capacity' => 18,
                'status' => $status,
            ] + $this->audit());

            foreach ($weekdays as $weekday) {
                DB::table('edu_class_schedules')->insert([
                    'class_id' => $id,
                    'weekday' => $weekday,
                    'start_time' => '18:00:00',
                    'end_time' => '19:30:00',
                ] + $this->ts());
            }

            $result[] = [
                'id' => $id, 'course_id' => $courseId, 'teacher_id' => $teacherId,
                'room_id' => $roomId, 'plan_id' => $plans[$courseId], 'status' => $status,
                'weekdays' => $weekdays, 'start_offset' => $startOffset, 'end_offset' => $endOffset,
            ];
        }

        // GV002 trợ giảng lớp Kids A1 — co-teacher ownership path of TeacherScope.
        DB::table('edu_class_teacher')->insert([
            'class_id' => $result[0]['id'],
            'teacher_id' => $teachers[1],
            'role' => 'assistant',
        ] + $this->ts());

        return $result;
    }

    private function classRoster(array $classes, array $students): void
    {
        // Active class: 4 active students. Upcoming: Chi. Completed: Em + Phúc.
        $rows = [
            [$classes[0]['id'], $students[0], 'active', -30],
            [$classes[0]['id'], $students[1], 'active', -30],
            [$classes[0]['id'], $students[2], 'active', -25],
            [$classes[0]['id'], $students[3], 'active', -20],
            [$classes[1]['id'], $students[2], 'active', -5],
            [$classes[2]['id'], $students[4], 'completed', -180],
            [$classes[2]['id'], $students[5], 'dropped', -180],
        ];
        foreach ($rows as [$classId, $studentId, $status, $enrolledOffset]) {
            DB::table('edu_class_students')->insert([
                'class_id' => $classId,
                'student_id' => $studentId,
                'status' => $status,
                'enrolled_at' => now()->addDays($enrolledOffset)->toDateString(),
            ] + $this->audit());
        }
    }

    private function timetables(array $classes, array $teachers, array $rooms): void
    {
        foreach ($classes as $i => $class) {
            $start = now()->addDays($class['start_offset'])->startOfDay();
            $end = now()->addDays($class['end_offset'])->startOfDay();
            $status = match ($class['status']) {
                'completed' => 'completed',
                default => 'active',
            };

            $timetableId = DB::table('edu_timetables')->insertGetId([
                'timetable_code' => 'TKB'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'name' => 'Thời khóa biểu lớp '.($i + 1),
                'course_id' => $class['course_id'],
                'class_room_id' => $class['id'],
                'teacher_id' => $class['teacher_id'],
                'room_id' => $class['room_id'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'schedule_pattern' => 'fixed_weekly',
                'total_sessions' => 0,
                'status' => $status,
            ] + $this->ts());

            foreach ($class['weekdays'] as $dayOfWeek) {
                DB::table('edu_timetable_rules')->insert([
                    'timetable_id' => $timetableId,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => '18:00:00',
                    'end_time' => '19:30:00',
                ] + $this->ts());
            }

            $no = 0;
            $today = now()->startOfDay();
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if (! in_array($date->dayOfWeekIso, $class['weekdays'], true)) {
                    continue;
                }
                $no++;
                DB::table('edu_sessions')->insert([
                    'class_id' => $class['id'],
                    'timetable_id' => $timetableId,
                    'session_no' => $no,
                    'name' => 'Buổi '.$no,
                    'session_date' => $date->toDateString(),
                    'start_time' => '18:00:00',
                    'end_time' => '19:30:00',
                    'teacher_id' => $class['teacher_id'],
                    'room_id' => $class['room_id'],
                    'status' => $date->lt($today) ? 'completed' : 'upcoming',
                    'attendance_locked' => false,
                ] + $this->ts());
            }

            DB::table('edu_timetables')->where('id', $timetableId)->update(['total_sessions' => $no]);
        }
    }

    /**
     * Class lessons snapshotted from the plan templates. The active class gets a
     * live spread (completed → in progress → scheduled); the completed class is
     * fully locked history.
     *
     * @return array<int, int[]> class_id => lesson ids (by lesson_no)
     */
    private function lessons(array $classes, array $plans, array $templates, array $teachers, array $rooms): array
    {
        $byClass = [];
        foreach ([$classes[0], $classes[2]] as $class) {
            $planTemplates = $templates[$class['plan_id']];
            $isHistory = $class['status'] === 'completed';

            foreach ($planTemplates as $i => $template) {
                // Active class: 4 past, 1 today, 3 future. History class: all past.
                $dayOffset = $isHistory ? -170 + $i * 4 : ($i - 4) * 7;
                $status = $isHistory
                    ? ($i < 7 ? 'locked' : 'completed')
                    : match (true) {
                        $i < 3 => 'completed',
                        $i === 3 => 'locked',
                        $i === 4 => 'in_progress',
                        default => 'scheduled',
                    };
                $isDone = in_array($status, ['completed', 'locked'], true);
                $snapshot = $template['snapshot'];

                $byClass[$class['id']][] = DB::table('edu_lessons')->insertGetId([
                    'class_room_id' => $class['id'],
                    'lesson_plan_id' => $class['plan_id'],
                    'lesson_plan_lesson_id' => $template['id'],
                    'lesson_no' => $i + 1,
                    'lesson_title' => $snapshot['lesson_title'],
                    'avatar' => 'assets/demo/lesson-'.($i + 1).'.png',
                    'lesson_date' => now()->addDays($dayOffset)->toDateString(),
                    'start_time' => '18:00:00',
                    'end_time' => '19:30:00',
                    'room_id' => $class['room_id'],
                    'teacher_id' => $class['teacher_id'],
                    'objective' => $snapshot['objective'],
                    'vocabulary' => $snapshot['vocabulary'],
                    'grammar' => $snapshot['grammar'],
                    'activities' => $snapshot['activities'],
                    'homework' => $snapshot['homework'],
                    'lesson_note' => $isDone ? 'Lớp học sôi nổi, hoàn thành mục tiêu bài '.($i + 1) : null,
                    'status' => $status,
                    'completed_at' => $isDone ? now()->addDays($dayOffset)->setTime(19, 30) : null,
                    'locked_at' => $status === 'locked' ? now()->addDays($dayOffset)->addDays(7) : null,
                ] + $this->audit());
            }
        }

        return $byClass;
    }

    /**
     * Attendance for the active class's completed sessions — mostly present,
     * with one late / absent / excused case each so every status shows up.
     */
    private function attendance(array $classes, array $students): void
    {
        $sessionIds = DB::table('edu_sessions')
            ->where('class_id', $classes[0]['id'])
            ->where('status', 'completed')
            ->orderBy('session_no')
            ->limit(6)
            ->pluck('id');

        $roster = array_slice($students, 0, 4);

        foreach ($sessionIds as $s => $sessionId) {
            foreach ($roster as $r => $studentId) {
                // Sprinkle non-present statuses over the grid deterministically.
                $status = match (true) {
                    $s === 1 && $r === 1 => 'late',
                    $s === 2 && $r === 3 => 'absent',
                    $s === 4 && $r === 2 => 'excused',
                    default => 'present',
                };
                DB::table('edu_attendances')->insert([
                    'session_id' => $sessionId,
                    'student_id' => $studentId,
                    'status' => $status,
                    'checkin_time' => in_array($status, ['present', 'late'], true)
                        ? now()->setTime(18, $status === 'late' ? 20 : 0)
                        : null,
                    'checkout_time' => $status === 'present' ? now()->setTime(19, 30) : null,
                    'note' => match ($status) {
                        'late' => 'Đi muộn 20 phút',
                        'absent' => 'Vắng không phép',
                        'excused' => 'Vắng có phép — đơn xin nghỉ',
                        default => null,
                    },
                ] + $this->audit());
            }
        }
    }

    // ── Enrollment → Invoice → Payment / Debt / Refund ──────────────────────

    private function enrollmentsAndBilling(array $classes, array $courses, array $students, array $parents, array $branches): void
    {
        // Finance accounts the payments land in.
        $cashAccountId = DB::table('fin_accounts')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $branches[0],
            'code' => 'ACC-CASH',
            'name' => 'Tiền mặt',
            'type' => 'cash',
            'currency' => 'VND',
            'balance' => 10000000,
            'status' => 'active',
        ] + $this->audit());
        $bankAccountId = DB::table('fin_accounts')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $branches[0],
            'code' => 'ACC-BANK',
            'name' => 'Vietcombank',
            'type' => 'bank',
            'currency' => 'VND',
            'balance' => 50000000,
            'bank_name' => 'Vietcombank',
            'account_number' => '0071000123456',
            'status' => 'active',
        ] + $this->audit());

        // [student idx, class idx, parent idx|null, enrollment status, paid ratio].
        $rows = [
            [0, 0, 0, 'studying', 1.0],   // Bé An — paid in full (cash)
            [1, 0, 0, 'studying', 0.5],   // Bé Bình — half paid → debt
            [2, 0, 1, 'studying', 1.0],   // Bé Chi — paid in full (bank)
            [3, 0, 2, 'studying', 0.0],   // Bé Dũng — unpaid → full debt
            [2, 1, 1, 'pending', 0.0],    // Bé Chi — IELTS upcoming, not yet billed-paid
            [4, 2, null, 'completed', 1.0], // Bé Em — finished course, settled
            [5, 2, null, 'cancelled', 1.0], // Bé Phúc — cancelled → refunded
        ];

        $totalLessons = 24;
        foreach ($rows as $i => [$s, $c, $p, $status, $paidRatio]) {
            $class = $classes[$c];
            $price = $class['course_id'] === $courses[0] ? 200000 : 250000;
            $tuition = $price * $totalLessons;
            $paid = (int) round($tuition * $paidRatio);
            $completed = $status === 'completed' ? $totalLessons : ($c === 0 ? 4 : 0);

            $enrollmentId = DB::table('edu_enrollments')->insertGetId([
                'code' => 'ENR'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'business_id' => $this->businessId,
                'student_id' => $students[$s],
                'course_id' => $class['course_id'],
                'class_id' => $class['id'],
                'sales_id' => $this->adminId,
                'enrolled_at' => now()->subDays(30 - $i)->toDateString(),
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completed,
                'remaining_lessons' => $status === 'cancelled' ? 0 : $totalLessons - $completed,
                'price_per_lesson' => $price,
                'tuition_amount' => $tuition,
                'discount_amount' => 0,
                'paid_amount' => $paid,
                'debt_amount' => $status === 'cancelled' ? 0 : $tuition - $paid,
                'status' => $status,
                'note' => 'Ghi danh demo '.($i + 1),
                'progress' => (int) round($completed / $totalLessons * 100),
            ] + $this->audit());

            $invoiceStatus = match (true) {
                $status === 'cancelled' => 'refunded',
                $paidRatio >= 1.0 => 'paid',
                $paidRatio > 0 => 'partial',
                default => 'pending_payment',
            };

            $invoiceId = DB::table('fin_invoices')->insertGetId([
                'business_id' => $this->businessId,
                'branch_id' => $branches[0],
                'invoice_type' => 'receivable',
                'partner_type' => 'student',
                'partner_id' => $students[$s],
                'student_id' => $students[$s],
                'parent_id' => $p !== null ? $parents[$p] : null,
                'enrollment_id' => $enrollmentId,
                'code' => 'INV-'.now()->format('Ym').'-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'subtotal' => $tuition,
                'discount' => 0,
                'tax' => 0,
                'total' => $tuition,
                'paid_amount' => $paid,
                'balance_amount' => $tuition - $paid,
                'invoice_date' => now()->subDays(30 - $i)->toDateString(),
                'status' => $invoiceStatus,
                'due_date' => now()->addDays(30)->toDateString(),
                'paid_at' => $paidRatio >= 1.0 ? now()->subDays(28 - $i) : null,
                'note' => 'Học phí ghi danh ENR'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'created_by' => $this->adminId,
                'updated_by' => $this->adminId,
            ] + $this->ts());

            DB::table('fin_invoice_items')->insert([
                'invoice_id' => $invoiceId,
                'name' => 'Học phí khóa học ('.$totalLessons.' buổi)',
                'quantity' => 1,
                'unit_price' => $tuition,
                'total' => $tuition,
            ] + $this->ts());

            if ($paid > 0) {
                DB::table('fin_payments')->insert([
                    'payment_no' => 'PMT-'.now()->format('Ym').'-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                    'payment_date' => now()->subDays(28 - $i)->toDateString(),
                    'business_id' => $this->businessId,
                    'branch_id' => $branches[0],
                    'student_id' => $students[$s],
                    'enrollment_id' => $enrollmentId,
                    'invoice_id' => $invoiceId,
                    'account_id' => $i % 2 === 0 ? $cashAccountId : $bankAccountId,
                    'amount' => $paid,
                    'currency' => 'VND',
                    'method' => $i % 2 === 0 ? 'cash' : 'bank_transfer',
                    'status' => 'confirmed',
                    'paid_at' => now()->subDays(28 - $i),
                    'confirmed_by' => $this->adminId,
                    'confirmed_at' => now()->subDays(28 - $i),
                    'created_by' => $this->adminId,
                ] + $this->ts());
            }

            // Outstanding balance → a debt record (unpaid | partial).
            if ($status !== 'cancelled' && $paid < $tuition) {
                DB::table('fin_debts')->insert([
                    'business_id' => $this->businessId,
                    'student_id' => $students[$s],
                    'invoice_id' => $invoiceId,
                    'amount' => $tuition,
                    'paid_amount' => $paid,
                    'remaining_amount' => $tuition - $paid,
                    'status' => $paid > 0 ? 'partial' : 'unpaid',
                    'due_date' => now()->addDays(30)->toDateString(),
                    'note' => 'Công nợ học phí',
                ] + $this->ts());
            }

            // Cancelled enrollment → refund of the unused lessons.
            if ($status === 'cancelled') {
                DB::table('fin_refunds')->insert([
                    'business_id' => $this->businessId,
                    'invoice_id' => $invoiceId,
                    'student_id' => $students[$s],
                    'amount' => (int) round($tuition * 0.8),
                    'reason' => 'Hoàn phí do học viên nghỉ giữa khóa',
                    'status' => 'completed',
                    'refunded_at' => now()->subDays(20),
                    'created_by' => $this->adminId,
                ] + $this->ts());
            }
        }
    }

    // ── Homework ─────────────────────────────────────────────────────────────

    private function assignments(array $classes, array $courses, array $levels, array $students, array $lessons): void
    {
        $class = $classes[0];
        $classLessons = $lessons[$class['id']];
        $roster = array_slice($students, 0, 4);

        // [name, type, lesson idx, due offset, status, submissions per roster slot].
        $rows = [
            ['Bài tập Unit 1 — My Family', 'homework', 0, -20, 'published', ['graded', 'late_submitted', 'submitted', 'assigned']],
            ['Worksheet Unit 2 — At School', 'worksheet', 1, 3, 'published', ['submitted', 'assigned', 'assigned', 'assigned']],
            ['Quiz Unit 3 — My Body (nháp)', 'quiz', 2, 10, 'draft', null],
        ];

        foreach ($rows as $i => [$name, $type, $lessonIdx, $dueOffset, $status, $submissions]) {
            $assignmentId = DB::table('edu_assignments')->insertGetId([
                'assignment_code' => 'ASG'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'assignment_name' => $name,
                'assignment_type' => $type,
                'avatar' => 'assets/demo/assignment-'.($i + 1).'.png',
                'course_id' => $class['course_id'],
                'level_id' => $levels[$class['course_id']][0],
                'lesson_id' => $classLessons[$lessonIdx],
                'class_room_id' => $class['id'],
                'description' => 'Mô tả '.$name,
                'instruction' => 'Hoàn thành trước hạn nộp.',
                'max_score' => 10,
                'due_date' => now()->addDays($dueOffset),
                'allow_late_submission' => true,
                'allow_multiple_submission' => false,
                'status' => $status,
            ] + $this->audit());

            if ($submissions === null) {
                continue;
            }

            foreach ($roster as $r => $studentId) {
                $subStatus = $submissions[$r];
                $submitted = $subStatus !== 'assigned';
                $graded = in_array($subStatus, ['graded', 'reviewed'], true);

                DB::table('edu_assignment_targets')->insert([
                    'assignment_id' => $assignmentId,
                    'student_id' => $studentId,
                    'assigned_at' => now()->addDays($dueOffset - 7),
                ] + $this->ts());
                DB::table('edu_assignment_submissions')->insert([
                    'assignment_id' => $assignmentId,
                    'student_id' => $studentId,
                    'submitted_at' => $submitted ? now()->addDays($dueOffset - 1) : null,
                    'answer' => $submitted ? 'Bài làm của học viên' : null,
                    'score' => $graded ? 9.0 : null,
                    'comment' => $graded ? 'Làm bài tốt, cần chú ý chính tả' : null,
                    'result_published' => $graded,
                    'status' => $subStatus,
                ] + $this->audit());
            }
        }
    }

    // ── Exams (sitting → grading → publication → promotion) ─────────────────

    private function examSession(int $finalExamId, array $class, int $roomId, int $invigilatorId, array $students, int $kidsCourseId, array $kidsLevels): void
    {
        $sessionId = DB::table('edu_exam_sessions')->insertGetId([
            'exam_id' => $finalExamId,
            'class_room_id' => $class['id'],
            'room_id' => $roomId,
            'teacher_id' => $invigilatorId,
            'exam_date' => now()->subDays(3)->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
            'status' => 'closed',
        ] + $this->audit());

        // [student idx, registration status, total score|null, published].
        $rows = [
            [0, 'published', 92.0, true],   // Bé An — excellent, promoted below
            [1, 'published', 55.0, true],   // Bé Bình — below passing score
            [2, 'graded', 78.0, false],     // Bé Chi — graded, not yet published
            [3, 'absent', null, false],     // Bé Dũng — no-show
        ];

        $topResultId = null;
        foreach ($rows as [$s, $regStatus, $total, $published]) {
            DB::table('edu_exam_registrations')->insert([
                'exam_session_id' => $sessionId,
                'student_id' => $students[$s],
                'status' => $regStatus,
            ] + $this->audit());

            if ($total === null) {
                continue;
            }

            $passed = $total >= 60;
            $resultId = DB::table('edu_exam_results')->insertGetId([
                'exam_session_id' => $sessionId,
                'student_id' => $students[$s],
                'listening_score' => $total / 4,
                'reading_score' => $total / 4,
                'grammar_score' => $total / 4,
                'vocabulary_score' => $total / 4,
                'total_score' => $total,
                'grade' => match (true) {
                    $total >= 90 => 'excellent',
                    $total >= 80 => 'good',
                    $total >= 70 => 'pass',
                    default => 'fail',
                },
                'passed' => $passed,
                'published_at' => $published ? now()->subDay() : null,
            ] + $this->audit());

            if ($s === 0) {
                $topResultId = $resultId;
            }
        }

        // Bé An passes with distinction → promoted Starter → Mover, exam-linked.
        $studentLevelId = DB::table('edu_student_levels')
            ->where('student_id', $students[0])
            ->value('id');

        DB::table('edu_student_levels')->where('id', $studentLevelId)->update([
            'level_id' => $kidsLevels[1],
            'assigned_at' => now()->subDay(),
            'assigned_by' => $this->adminId,
            'updated_at' => now(),
        ]);

        DB::table('edu_student_level_histories')->insert([
            'student_level_id' => $studentLevelId,
            'business_id' => $this->businessId,
            'student_id' => $students[0],
            'from_level_id' => $kidsLevels[0],
            'to_level_id' => $kidsLevels[1],
            'source' => 'promote',
            'action' => 'promote',
            'reason' => 'Xét lên cấp theo kết quả thi cuối khóa',
            'score' => 92.0,
            'exam_result_id' => $topResultId,
            'created_by' => $this->adminId,
            'effective_at' => now()->subDay(),
        ] + $this->ts());
    }

    // ── Evaluations ──────────────────────────────────────────────────────────

    private function evaluations(array $teachers, array $students, array $parents, int $kidsCourseId, array $class, array $lessons): void
    {
        $criteriaFor = [
            'teacher' => ['expertise', 'teaching_method', 'communication', 'attitude'],
            'student' => ['knowledge', 'pronunciation', 'grammar', 'homework'],
        ];

        // [type, target, evaluator type, evaluator, period, scores, status].
        $rows = [
            ['student', $students[0], 'teacher', $teachers[0], 'monthly', [5, 4, 5, 5], 'approved'],
            ['student', $students[1], 'teacher', $teachers[0], 'monthly', [3, 3, 3, 2], 'submitted'],
            ['student', $students[2], 'teacher', $teachers[1], 'monthly', [4, 4, 4, 4], 'draft'],
            ['teacher', $teachers[0], 'parent', $parents[0], 'course', [5, 5, 4, 5], 'approved'],
        ];

        foreach ($rows as $seq => [$type, $targetId, $evaluatorType, $evaluatorId, $period, $scores, $status]) {
            $keys = $criteriaFor[$type];
            $criteria = [];
            foreach ($scores as $i => $score) {
                $criteria[] = ['criterion' => $keys[$i], 'score' => $score];
            }
            $average = round(array_sum($scores) / count($scores), 2);

            DB::table('edu_evaluations')->insert([
                'evaluation_code' => 'EVAL'.str_pad((string) ($seq + 1), 6, '0', STR_PAD_LEFT),
                'evaluation_type' => $type,
                'target_id' => $targetId,
                'evaluator_type' => $evaluatorType,
                'evaluator_id' => $evaluatorId,
                'course_id' => $kidsCourseId,
                'class_room_id' => $class['id'],
                'evaluation_period' => $period,
                'criteria' => json_encode($criteria),
                'score' => $average,
                'classification' => match (true) {
                    $average >= 4.5 => 'excellent',
                    $average >= 3.5 => 'good',
                    $average >= 2.5 => 'average',
                    default => 'weak',
                },
                'comment' => 'Nhận xét kỳ đánh giá '.($seq + 1),
                'strengths' => 'Tiến bộ rõ về từ vựng và phát âm',
                'weaknesses' => 'Cần luyện thêm ngữ pháp',
                'recommendations' => 'Duy trì làm bài tập về nhà đều đặn',
                'status' => $status,
                'evaluated_at' => now()->subDays(5 - $seq),
            ] + $this->audit());
        }
    }

    // ── Leave requests ───────────────────────────────────────────────────────

    private function leaveRequests(array $students, array $teachers, array $class, array $lessons): void
    {
        $classLessons = $lessons[$class['id']];

        // [type, requester, lesson idx, status, reason type].
        $rows = [
            ['student_leave', $students[1], 5, 'pending', 'sick'],
            ['student_leave', $students[2], 6, 'approved', 'family'],
            ['teacher_leave', $teachers[0], 7, 'approved', 'personal'],
        ];

        foreach ($rows as $i => [$type, $requesterId, $lessonIdx, $status, $reasonType]) {
            $isTeacher = $type === 'teacher_leave';
            $lessonId = $classLessons[$lessonIdx];
            $leaveDate = DB::table('edu_lessons')->where('id', $lessonId)->value('lesson_date');

            $leaveId = DB::table('edu_leave_requests')->insertGetId([
                'request_code' => 'LR'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'request_type' => $type,
                'requester_type' => $isTeacher ? 'teacher' : 'student',
                'requester_id' => $requesterId,
                'class_room_id' => $class['id'],
                'lesson_id' => $lessonId,
                'leave_date' => $leaveDate,
                'reason_type' => $reasonType,
                'reason' => 'Lý do nghỉ '.($i + 1),
                'attachment_file_id' => $this->file($i),
                'status' => $status,
                'approved_by' => $status === 'approved' ? $this->adminId : null,
                'approved_at' => $status === 'approved' ? now() : null,
            ] + $this->audit());

            DB::table('edu_leave_request_logs')->insert([
                'leave_request_id' => $leaveId,
                'action' => 'created',
                'new_status' => 'pending',
                'note' => 'Tạo đơn nghỉ',
                'created_by' => $this->adminId,
            ] + $this->ts());

            // BR007: an approved student leave earns a make-up entitlement.
            if ($status === 'approved' && ! $isTeacher) {
                DB::table('edu_makeup_lessons')->insert([
                    'leave_request_id' => $leaveId,
                    'student_id' => $requesterId,
                    'original_lesson_id' => $lessonId,
                    'status' => 'waiting',
                ] + $this->audit());
            }
        }
    }

    // ── Wallets, promotions, tasks ───────────────────────────────────────────

    private function wallets(array $parents): void
    {
        foreach (array_slice($parents, 0, 2) as $i => $parentId) {
            $walletId = DB::table('fin_wallets')->insertGetId([
                'business_id' => $this->businessId,
                'wallet_code' => 'WAL'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'owner_type' => 'parent',
                'owner_id' => $parentId,
                'available_balance' => 450000,
                'bonus_balance' => 0,
                'frozen_balance' => 0,
                'currency' => 'VND',
                'status' => 'active',
            ] + $this->ts());

            // deposit 500k → payment 50k.
            $trail = [
                ['deposit', 500000, 0, 500000],
                ['payment', 50000, 500000, 450000],
            ];
            foreach ($trail as $t => [$type, $amount, $before, $after]) {
                DB::table('fin_wallet_transactions')->insert([
                    'business_id' => $this->businessId,
                    'wallet_id' => $walletId,
                    'transaction_code' => 'WTX'.str_pad((string) ($i * 2 + $t + 1), 6, '0', STR_PAD_LEFT),
                    'transaction_type' => $type,
                    'reference_type' => 'wallet',
                    'reference_id' => $walletId,
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'description' => 'Giao dịch demo '.$type,
                    'created_by' => $this->adminId,
                ] + $this->ts());
            }
        }
    }

    private function promotions(array $parents): void
    {
        $promotionId = DB::table('fin_promotions')->insertGetId([
            'promotion_code' => 'PROMO-HE',
            'promotion_name' => 'Ưu đãi hè',
            'promotion_type' => 'discount',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(50)->toDateString(),
            'status' => 'active',
            'priority' => 1,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'max_discount' => 500000,
            'approved_by' => $this->adminId,
            'approved_at' => now()->subDays(10),
        ] + $this->audit());

        DB::table('fin_vouchers')->insert([
            'promotion_id' => $promotionId,
            'voucher_code' => 'HANASUMMER10',
            'usage_limit' => 100,
            'used_count' => 1,
            'expired_at' => now()->addDays(50),
            'status' => 'active',
        ] + $this->audit());
    }

    private function tasks(): void
    {
        if (! Schema::hasTable('task_tasks')) {
            return;
        }

        $rows = [
            ['Chuẩn bị giáo án tuần sau', 'academic', 'high', 'in_progress', 50],
            ['Gọi phụ huynh nhắc học phí', 'finance', 'medium', 'open', 0],
            ['Tổng kết điểm cuối khóa', 'academic', 'high', 'completed', 100],
        ];
        foreach ($rows as $i => [$title, $category, $priority, $status, $progress]) {
            DB::table('task_tasks')->insert([
                'task_code' => 'TASK'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'title' => $title,
                'description' => $title,
                'category' => $category,
                'priority' => $priority,
                'status' => $status,
                'progress' => $progress,
                'start_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'completed_at' => $status === 'completed' ? now() : null,
            ] + $this->ts());
        }
    }
}
