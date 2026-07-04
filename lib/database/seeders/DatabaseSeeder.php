<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BusinessAndUserSeeder::class,
            RoleSeeder::class,
            BusinessPermissionSeeder::class,
            BranchPermissionSeeder::class,
            UserPermissionSeeder::class,
            TeacherPermissionSeeder::class,
            StudentPermissionSeeder::class,
            LeadPermissionSeeder::class,
            ParentPermissionSeeder::class,
            ParentStudentPermissionSeeder::class,
            CoursePermissionSeeder::class,
            LevelPermissionSeeder::class,
            StudentLevelPermissionSeeder::class,
            ClassRoomPermissionSeeder::class,
            ClassSessionPermissionSeeder::class,
            AttendancePermissionSeeder::class,
            RoomPermissionSeeder::class,
            LeaveRequestPermissionSeeder::class,
            LessonPlanPermissionSeeder::class,
            LessonPermissionSeeder::class,
            MaterialPermissionSeeder::class,
            AssignmentPermissionSeeder::class,
            ExamPermissionSeeder::class,
            QuestionPermissionSeeder::class,
            EnrollmentPermissionSeeder::class,
            EvaluationPermissionSeeder::class,
            InvoicePermissionSeeder::class,
            AccountPermissionSeeder::class,
            PaymentPermissionSeeder::class,
            PromotionPermissionSeeder::class,
            WalletPermissionSeeder::class,
            DebtPermissionSeeder::class,
            ActivityLogPermissionSeeder::class,
            TaskPermissionSeeder::class,
            TimetablePermissionSeeder::class,
            RolePermissionSeeder::class,

            MasterSeeder::class,
        ]);
    }
}
