<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

abstract class PermissionSeeder extends Seeder
{
    /**
     * Idempotently upsert a feature's permissions into sys_permissions.
     *
     * @param  array<string, string>  $permissions  code => display name
     */
    protected function seedPermissions(string $module, string $feature, array $permissions): void
    {
        foreach ($permissions as $code => $name) {
            DB::table('sys_permissions')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'guard_name' => 'api',
                    'module' => $module,
                    'feature' => $feature,
                    'description' => $name,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
