<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Seed the standard system roles for the default business.
     */
    public function run(): void
    {
        $businessId = DB::table('sys_business')->min('id');

        if (! $businessId) {
            return;
        }

        $roles = [
            ['code' => 'BUSINESS_ADMIN', 'title' => 'Business Admin'],
            ['code' => 'BRANCH_MANAGER', 'title' => 'Branch Manager'],
            ['code' => 'TEACHER', 'title' => 'Teacher'],
            ['code' => 'STAFF', 'title' => 'Staff'],
            ['code' => 'ACCOUNTANT', 'title' => 'Accountant'],
            ['code' => 'CRM_STAFF', 'title' => 'CRM Staff'],
            ['code' => 'ACADEMIC_STAFF', 'title' => 'Academic Staff'],
        ];

        foreach ($roles as $role) {
            DB::table('sys_roles')->updateOrInsert(
                ['code' => $role['code']],
                [
                    'business_id' => $businessId,
                    'title' => $role['title'],
                    'type' => 'system',
                    'guard_name' => 'api',
                    'description' => $role['title'],
                    'is_active' => true,
                    'is_default' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Default role for individual (business-less) self-registered users, scoped to the
        // system business since sys_roles.business_id stays NOT NULL.
        DB::table('sys_roles')->updateOrInsert(
            ['code' => 'INDIVIDUAL_USER'],
            [
                'business_id' => $businessId,
                'title' => 'Individual User',
                'type' => 'user',
                'guard_name' => 'api',
                'description' => 'Default role for individual self-registered accounts',
                'is_active' => true,
                'is_default' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
