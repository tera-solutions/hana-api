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
     * Use '*' to grant every seeded permission.
     */
    private array $map = [
        'BUSINESS_ADMIN' => ['*'],

        'BRANCH_MANAGER' => [
            'business.view',
            'branch.list', 'branch.view', 'branch.update',
            'user.list', 'user.view', 'user.create', 'user.update',
            'teacher.list', 'teacher.view', 'teacher.create', 'teacher.update', 'teacher.delete',
        ],

        'ACADEMIC_STAFF' => [
            'business.view', 'branch.list', 'branch.view',
            'teacher.list', 'teacher.view', 'teacher.create', 'teacher.update',
        ],

        'TEACHER' => [
            'teacher.list', 'teacher.view',
        ],

        'STAFF' => [
            'business.view', 'branch.view', 'teacher.list', 'teacher.view',
        ],

        'ACCOUNTANT' => [
            'business.view', 'branch.view', 'user.view',
        ],

        'CRM_STAFF' => [
            'business.view', 'branch.view', 'user.list', 'user.view',
        ],
    ];

    public function run(): void
    {
        $permissions = DB::table('sys_permissions')->pluck('id', 'code'); // code => id
        $allCodes = $permissions->keys()->all();

        foreach ($this->map as $roleCode => $permissionCodes) {
            $roleId = DB::table('sys_roles')->where('code', $roleCode)->value('id');

            if (! $roleId) {
                continue;
            }

            $codes = in_array('*', $permissionCodes, true) ? $allCodes : $permissionCodes;

            foreach ($codes as $code) {
                $permissionId = $permissions[$code] ?? null;

                if (! $permissionId) {
                    continue;
                }

                DB::table('role_has_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['code' => $roleId.'.'.$code],
                );
            }
        }
    }
}
