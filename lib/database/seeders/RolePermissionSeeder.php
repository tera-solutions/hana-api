<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Grant permissions to each role.
     *
     * Super Admin is intentionally omitted — those accounts use the `is_admin`
     * flag and bypass the permission checks entirely.
     *
     * Each role is described by the modules it can manage:
     *   - `all`  => every seeded permission (BUSINESS_ADMIN).
     *   - `full` => every action of the listed module prefixes (e.g. course.* ).
     *   - `view` => the read actions (`.list` / `.view`) of the listed prefixes.
     *
     * Module prefix = the part before the first dot (course, student_level,
     * fin_invoice, …). New permissions are picked up automatically.
     */
    private const READ_ACTIONS = ['list', 'view'];

    private array $map = [
        'BUSINESS_ADMIN' => ['all' => true],

        'BRANCH_MANAGER' => [
            'full' => [
                'branch', 'user', 'teacher', 'student', 'class', 'enrollment', 'room',
                'lesson', 'course', 'level', 'student_level', 'lesson_plan', 'material', 'assignment',
                'timetable', 'leave', 'evaluation', 'task',
            ],
            'view' => [
                'business', 'session', 'crm_lead', 'parent', 'parent_student',
                'fin_invoice', 'fin_payment', 'fin_account', 'fin_debt', 'activity_log',
                'exam', 'question', 'attendance', 'promotion', 'wallet',
            ],
        ],

        'ACADEMIC_STAFF' => [
            'full' => [
                'course', 'level', 'student_level', 'class', 'room', 'lesson', 'session',
                'lesson_plan', 'material', 'assignment', 'student', 'enrollment',
                'exam', 'question', 'timetable', 'leave', 'evaluation', 'task',
            ],
            'view' => ['business', 'branch', 'teacher', 'parent', 'parent_student', 'attendance'],
        ],

        'TEACHER' => [
            'full' => ['assignment', 'material', 'evaluation', 'task', 'attendance'],
            'view' => [
                'student', 'student_level', 'lesson', 'session', 'class', 'course', 'level', 'lesson_plan',
                'exam', 'question', 'timetable', 'leave', 'dashboard',
            ],
        ],

        'STAFF' => [
            'full' => ['student', 'parent', 'parent_student', 'task'],
            'view' => [
                'business', 'branch', 'course', 'class', 'room', 'enrollment', 'crm_lead', 'lesson', 'timetable',
            ],
        ],

        'ACCOUNTANT' => [
            'full' => ['fin_invoice', 'fin_payment', 'fin_account', 'fin_debt', 'wallet', 'promotion', 'task'],
            'view' => ['business', 'branch', 'student', 'enrollment', 'parent'],
        ],

        'CRM_STAFF' => [
            'full' => ['crm_lead', 'parent', 'parent_student', 'task'],
            'view' => ['business', 'branch', 'student', 'enrollment', 'course', 'class', 'promotion'],
        ],
    ];

    public function run(): void
    {
        $permissions = DB::table('sys_permissions')->pluck('id', 'code'); // code => id

        foreach ($this->map as $roleCode => $config) {
            $roleId = DB::table('sys_roles')->where('code', $roleCode)->value('id');

            if (! $roleId) {
                continue;
            }

            foreach ($permissions as $code => $permissionId) {
                if (! $this->grants($config, $code)) {
                    continue;
                }

                DB::table('role_has_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['code' => $roleId.'.'.$code],
                );
            }
        }
    }

    /**
     * Whether the role described by $config is granted the given permission code.
     */
    private function grants(array $config, string $code): bool
    {
        if ($config['all'] ?? false) {
            return true;
        }

        [$prefix, $action] = array_pad(explode('.', $code, 2), 2, '');

        if (in_array($prefix, $config['full'] ?? [], true)) {
            return true;
        }

        return in_array($prefix, $config['view'] ?? [], true)
            && in_array($action, self::READ_ACTIONS, true);
    }
}
