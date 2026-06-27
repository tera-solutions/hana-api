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

        $roleAdminId = DB::table('sys_roles')->insertGetId([
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

        $roleTeacherId = DB::table('sys_roles')->insertGetId([
            'business_id' => $businessId,
            'title' => 'Teacher',
            'code' => 'TEACHER_ROLE',
            'type' => 'system',
            'guard_name' => 'api',
            'description' => 'Teacher to the system',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

         $roleStudentId = DB::table('sys_roles')->insertGetId([
            'business_id' => $businessId,
            'title' => 'Student',
            'code' => 'STUDENT_ROLE',
            'type' => 'system',
            'guard_name' => 'api',
            'description' => 'Student to the system',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            [
                'full_name' => 'Super Admin',
                'avatar' => 'https://api.anhnguhana.com/assets/avatar/01.png',
                'username' => 'super',
                'email' => 'super@terasolutions.vn',
                'status' => 'active',
                'code' => 'SA_001',
                'is_active' => true,
                'is_admin' => true,
                'password' => Hash::make('12345678'),
                'business_id' => $businessId,
                'role_id' => $roleAdminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Admin',
                'avatar' => 'https://api.anhnguhana.com/assets/avatar/02.png',
                'username' => 'admin',
                'email' => 'admin@terasolutions.vn',
                'status' => 'active',
                'code' => 'SA_002',
                'is_active' => true,
                'is_admin' => true,
                'password' => Hash::make('12345678'),
                'business_id' => $businessId,
                'role_id' => $roleAdminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Cô Hạ',
                'avatar' => 'https://api.anhnguhana.com/assets/avatar/06.png',
                'username' => 'demo1',
                'email' => 'demo1@terasolutions.vn',
                'status' => 'active',
                'code' => 'SA_003',
                'is_active' => true,
                'is_admin' => true,
                'password' => Hash::make('12345678'),
                'business_id' => $businessId,
                'role_id' => $roleTeacherId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Nguyễn Van A',
                'avatar' => 'https://api.anhnguhana.com/assets/avatar/04.png',
                'username' => 'demo2',
                'email' => 'demo2@terasolutions.vn',
                'status' => 'active',
                'code' => 'SA_004',
                'is_active' => true,
                'is_admin' => true,
                'password' => Hash::make('12345678'),
                'business_id' => $businessId,
                'role_id' => $roleStudentId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}