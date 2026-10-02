<?php

namespace Database\Seeders;

use App\Models\SupportDepartment;
use Illuminate\Database\Seeder;

class SupportDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Phòng Công tác Sinh viên và Hỗ trợ',
                'code' => 'CTSV',
                'description' => 'Tiếp nhận và xử lý các yêu cầu hỗ trợ sinh viên.',
                'is_active' => true,
            ],
            [
                'name' => 'Phòng Đào Tạo',
                'code' => 'DT',
                'description' => 'Tiếp nhận các yêu cầu về học tập và đăng ký học phần.',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $department) {
            SupportDepartment::updateOrCreate(
                [
                    'code' => $department['code'],
                ],
                $department
            );
        }
    }
}
