<?php

namespace Database\Seeders;

use App\Models\SupportDepartment;
use App\Models\SupportType;
use Illuminate\Database\Seeder;

class SupportTypeSeeder extends Seeder
{
    public function run(): void
    {
        $ctsv = SupportDepartment::where(
            'code',
            'CTSV'
        )->firstOrFail();

        $daoTao = SupportDepartment::where(
            'code',
            'DT'
        )->firstOrFail();

        $supportTypes = [
            [
                'name' => 'Cấp giấy xác nhận sinh viên',
                'code' => 'XNSV',
                'description' => 'Hỗ trợ cấp giấy xác nhận đang học tại trường.',
                'department_id' => $ctsv->id,
                'is_active' => true,
            ],
            [
                'name' => 'Hỗ trợ học phí',
                'code' => 'HTHP',
                'description' => 'Hỗ trợ các vấn đề liên quan đến học phí.',
                'department_id' => $ctsv->id,
                'is_active' => true,
            ],
            [
                'name' => 'Đăng ký và điều chỉnh học phần',
                'code' => 'DKHP',
                'description' => 'Hỗ trợ đăng ký, hủy và điều chỉnh học phần.',
                'department_id' => $daoTao->id,
                'is_active' => true,
            ],
            [
                'name' => 'Cấp bảng điểm',
                'code' => 'BD',
                'description' => 'Hỗ trợ yêu cầu cấp và tra cứu bảng điểm.',
                'department_id' => $daoTao->id,
                'is_active' => true,
            ],
        ];

        foreach ($supportTypes as $supportType) {
            SupportType::updateOrCreate(
                [
                    'code' => $supportType['code'],
                ],
                $supportType
            );
        }
    }
}
