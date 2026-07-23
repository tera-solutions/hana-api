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
     * invoice, …). New permissions are picked up automatically.
     */
    private const READ_ACTIONS = ['list', 'view'];

    private array $map = [
        'ADMIN_ROLE' => ['all' => true],

        // webs/teacher is an independent SaaS portal each center runs — there is
        // no separate admin portal per business, so the owning teacher account
        // has full run-the-center access to the catalog/roster modules below,
        // not just read access. Sensitive cross-cutting actions (approving own
        // wallet payouts, activating a shared question into the live bank,
        // running payroll) stay deliberately withheld — see the per-line notes.
        'TEACHER_ROLE' => [
            'full' => [
                'assignment', 'material', 'evaluation', 'evaluation_criteria_template', 'task', 'attendance',
                'lesson', 'session', 'leave', 'session_feedback', 'score', 'certificate',
                'student', 'student_level', 'class', 'course', 'level',
                'timetable', 'room', 'parent', 'dashboard', 'lead', 'notification',
                'achievement', 'teacher', 'branch', 'timesheet', 'business_bank_account', 'invoice_config',
            ],
            'actions' => [
                // Self-service payroll: teacher can view and (re)generate
                // their OWN payroll — PayrollController::generate() locks
                // this down server-side (own teacher_id only, bonus/penalty
                // ignored for non-admin) so a teacher still cannot set their
                // own bonus/penalty or touch anyone else's payroll.
                'payroll' => ['view', 'generate'],
                'lesson_plan' => ['list', 'view', 'create', 'update', 'publish'],
                'enrollment' => ['list', 'view', 'create', 'transfer'],
                'exam' => ['list', 'view', 'create', 'update'],
                // `adjust` lets the center admin correct a teacher's wallet balance
                // directly (deposit/payment/refund/lock stay withheld — those move
                // through the request/invoice flows below, not a raw balance edit).
                'wallet' => ['view', 'transaction.view', 'adjust'],
                // Request-based nạp/rút (no gateway): teacher creates/cancels/reads
                // their own request; `approve` (which also covers reject/complete)
                // is deliberately withheld — self-approving your own payout is a
                // fraud vector, an admin must review it from outside this app.
                'wallet_request' => ['list', 'view', 'create', 'cancel'],
                // Own HR profile payout target for withdraw requests — not `teacher.update`
                // (withheld from this role), scoped server-side to the acting user's own row.
                'bank_account' => ['view', 'update'],
                // Tuition invoices for the teacher's own students: full lifecycle
                // incl. approve/cancel/refund/pay (small centers have the teacher
                // double as billing staff, not a separate finance role).
                'invoice' => ['list', 'view', 'create', 'update', 'approve', 'cancel', 'refund', 'pay'],
                // Author + submit-for-review, same as lesson_plan; `approve`/`activate`
                // stay out — publishing a shared question into the live bank is a
                // reviewer/admin step, not the authoring teacher's own call.
                'question' => ['list', 'view', 'create', 'update', 'clone', 'review', 'archive', 'import', 'generate_exam'],
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
            'actions' => [
                // Own inbox only (service-scoped); no create/update/delete —
                // sending notifications is a teacher/admin action.
                'notification' => ['list', 'view', 'read'],
            ],
        ],

        // Children's progress + billing view; may file leave requests for a child.
        'PARENT_ROLE' => [
            'full' => ['leave'],
            'view' => [
                'student', 'class', 'course', 'level', 'session', 'attendance', 'evaluation',
                'assignment', 'timetable', 'invoice', 'payment', 'debt', 'dashboard',
            ],
            'actions' => [
                'teacher_review' => ['create'],
                // Own inbox only (service-scoped); no create/update/delete —
                // sending notifications is a teacher/admin action.
                'notification' => ['list', 'view', 'read'],
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
