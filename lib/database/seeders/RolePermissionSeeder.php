<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Grant permissions to the roles seeded by BusinessAndUserSeeder.
     *
     * ADMIN_ROLE accounts also carry the `is_admin` flag and bypass the
     * permission checks entirely; the full grant here keeps the role coherent
     * for non-flagged admins.
     *
     * Each role is described by the modules it can manage:
     *   - `all`  => every seeded permission.
     *   - `full` => every action of the listed module prefixes (e.g. course.* ).
     *   - `view` => the read actions (`.list` / `.view`) of the listed prefixes.
     *
     * Module prefix = the part before the first dot (course, student_level,
     * fin_invoice, …). New permissions are picked up automatically.
     */
    private const READ_ACTIONS = ['list', 'view'];

    private array $map = [
        'ADMIN_ROLE' => ['all' => true],

        // Daily teaching work: manages their classes' homework, attendance,
        // evaluations and lessons; reads the surrounding catalog. Row-level
        // isolation between teachers is enforced by TeacherScope, not here.
        'TEACHER_ROLE' => [
            'full' => [
                'assignment', 'material', 'evaluation', 'task', 'attendance',
                'lesson', 'session', 'leave',
            ],
            'view' => [
                'student', 'student_level', 'class', 'course', 'level', 'lesson_plan',
                'exam', 'question', 'timetable', 'room', 'parent', 'dashboard',
            ],
        ],

        // Own learning view: schedule, lessons, homework, results; may file
        // leave requests.
        'STUDENT_ROLE' => [
            'full' => ['leave'],
            'view' => [
                'class', 'course', 'level', 'lesson', 'session', 'assignment', 'material',
                'exam', 'timetable', 'evaluation', 'attendance', 'student_level', 'dashboard',
            ],
        ],

        // Children's progress + billing view; may file leave requests for a child.
        'PARENT_ROLE' => [
            'full' => ['leave'],
            'view' => [
                'student', 'class', 'course', 'level', 'session', 'attendance', 'evaluation',
                'assignment', 'timetable', 'fin_invoice', 'fin_payment', 'fin_debt', 'dashboard',
            ],
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
