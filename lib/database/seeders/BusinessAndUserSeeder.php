<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BusinessAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = DB::table('sys_business')->insertGetId([
            'name' => 'Hana English System',
            'tax_code' => '0102030405',
            'email' => 'info@hana.edu.vn',
            'phone' => '0987654321',
            'website' => 'https://hana.edu.vn',
            'logo' => 'domain',
            'address' => 'District 1, HCMC',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('sys_roles')->insertGetId([
            'business_id' => $businessId,
            'title' => 'Administrator',
            'code' => 'ADMIN_ROLE',
            'type' => 'system',
            'guard_name' => 'api',
            'description' => 'Full access to the system',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        DB::table('users')->insert([
            [
                'full_name' => 'Super Admin',
                'avatar' => 'shield-account',
                'username' => 'super',
                'email' => 'admin@terasolutions.vn',
                'status' => 'active',
                'code' => 'SA_001',
                'is_active' => true,
                'is_admin' => true,
                'password' => Hash::make('12345678'),
                'business_id' => $businessId,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}