<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Sync each role's permissions to $map — grants what's configured and
     * revokes anything previously granted that the current config no longer
     * allows, so narrowing a role's access here takes effect on re-seed.
     *
     * ADMIN_ROLE accounts also carry the `is_admin` flag and bypass the
     * permission checks entirely; the full grant here keeps the role coherent
     * for non-flagged admins.
     *
     * Each role is described by the modules it can manage:
     *   - `all`     => every seeded permission.
     *   - `full`    => every action of the listed module prefixes (e.g. course.* ).
     *   - `view`    => the read actions (`.list` / `.view`) of the listed prefixes.
     *   - `actions` => an explicit action allowlist per prefix, for a module
     *                  where a role needs more than `view` but less than `full`
     *                  (e.g. lesson_plan.* for TEACHER_ROLE, below).
     *
     * Module prefix = the part before the first dot (course, student_level,
     * fin_invoice, …). New permissions are picked up automatically.
     */
    private const READ_ACTIONS = ['list', 'view'];

    private array $map = [
        'ADMIN_ROLE' => ['all' => true],

        // Daily teaching work: authors draft lesson plans/templates (publishing
        // is an admin review step, not a teacher action) and manages their
        // classes' homework, attendance, evaluations and lessons; reads the
        // surrounding catalog. Row-level isolation between teachers is enforced
        // by TeacherScope, not here.
        'TEACHER_ROLE' => [
            'full' => [
                'assignment', 'material', 'evaluation', 'task', 'attendance',
                'lesson', 'session', 'leave',
            ],
            'view' => [
                'student', 'student_level', 'class', 'course', 'level',
                'exam', 'question', 'timetable', 'room', 'parent', 'dashboard',
            ],
            'actions' => [
                'lesson_plan' => ['list', 'view', 'create', 'update'],
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

            $grantedIds = [];

            foreach ($permissions as $code => $permissionId) {
                if (! $this->grants($config, $code)) {
                    continue;
                }

                $grantedIds[] = $permissionId;

                DB::table('role_has_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['code' => $roleId.'.'.$code],
                );
            }

            DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->whereNotIn('permission_id', $grantedIds)
                ->delete();
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

        if (isset($config['actions'][$prefix])) {
            return in_array($action, $config['actions'][$prefix], true);
        }

        if (in_array($prefix, $config['full'] ?? [], true)) {
            return true;
        }

        return in_array($prefix, $config['view'] ?? [], true)
            && in_array($action, self::READ_ACTIONS, true);
    }
}
