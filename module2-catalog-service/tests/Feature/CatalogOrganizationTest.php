<?php

namespace Tests\Feature;

use App\Models\SupportDepartment;
use App\Models\SupportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogOrganizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test tạo phòng ban.
     */
    public function test_can_create_support_department(): void
    {
        $department = SupportDepartment::create([
            'name' => 'Phòng Đào Tạo',
            'code' => 'DT',
            'description' => 'Phụ trách công tác đào tạo',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas(
            'support_departments',
            [
                'id' => $department->id,
                'name' => 'Phòng Đào Tạo',
                'code' => 'DT',
                'is_active' => true,
            ]
        );
    }

    /**
     * Test tạo loại hỗ trợ thuộc một phòng ban.
     */
    public function test_can_create_support_type_for_department(): void
    {
        $department = SupportDepartment::create([
            'name' => 'Phòng Công tác Sinh viên',
            'code' => 'CTSV',
            'description' => 'Hỗ trợ sinh viên',
            'is_active' => true,
        ]);

        $supportType = SupportType::create([
            'name' => 'Xác nhận sinh viên',
            'code' => 'XNSV',
            'description' => 'Cấp giấy xác nhận sinh viên',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas(
            'support_types',
            [
                'id' => $supportType->id,
                'code' => 'XNSV',
                'department_id' => $department->id,
                'is_active' => true,
            ]
        );
    }

    /**
     * Test quan hệ:
     * SupportType thuộc SupportDepartment.
     */
    public function test_support_type_belongs_to_department(): void
    {
        $department = SupportDepartment::create([
            'name' => 'Phòng Đào Tạo',
            'code' => 'DT',
            'is_active' => true,
        ]);

        $supportType = SupportType::create([
            'name' => 'Đăng ký học phần',
            'code' => 'DKHP',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->assertNotNull(
            $supportType->department
        );

        $this->assertEquals(
            $department->id,
            $supportType->department->id
        );

        $this->assertEquals(
            'DT',
            $supportType->department->code
        );
    }

    /**
     * Test một phòng ban có nhiều loại hỗ trợ.
     */
    public function test_department_has_many_support_types(): void
    {
        $department = SupportDepartment::create([
            'name' => 'Phòng Công tác Sinh viên',
            'code' => 'CTSV',
            'is_active' => true,
        ]);

        SupportType::create([
            'name' => 'Xác nhận sinh viên',
            'code' => 'XNSV',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        SupportType::create([
            'name' => 'Hỗ trợ học phí',
            'code' => 'HTHP',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->assertCount(
            2,
            $department->supportTypes
        );
    }

    /**
     * Test bật / tắt phòng ban.
     */
    public function test_department_can_be_deactivated(): void
    {
        $department = SupportDepartment::create([
            'name' => 'Phòng Đào Tạo',
            'code' => 'DT',
            'is_active' => true,
        ]);

        $department->update([
            'is_active' => false,
        ]);

        $this->assertDatabaseHas(
            'support_departments',
            [
                'id' => $department->id,
                'is_active' => false,
            ]
        );
    }
}