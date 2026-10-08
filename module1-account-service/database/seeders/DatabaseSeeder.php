<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'full_name' => 'Quan Tri Vien',
            'email' => 'admin@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000001',
            'role' => 'admin',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        User::factory()->create([
            'full_name' => 'Sinh Vien Test',
            'email' => 'student@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000002',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);
    }
}
