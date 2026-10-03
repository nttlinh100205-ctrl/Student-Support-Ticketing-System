<?php

namespace Tests\Unit;

use App\Models\SupportDepartment;
use App\Models\SupportType;
use App\Services\DepartmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DepartmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DepartmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DepartmentService;
    }

    public function test_create_defaults_to_active(): void
    {
        $department = $this->service->create(['name' => 'Phòng Đào Tạo', 'code' => 'DT']);

        $this->assertTrue($department->is_active);
        $this->assertSame(0, $department->staff_count);
    }

    public function test_paginate_filters_by_search_and_status(): void
    {
        SupportDepartment::create(['name' => 'Phòng Đào Tạo', 'code' => 'DT', 'is_active' => true]);
        SupportDepartment::create(['name' => 'Thư viện', 'code' => 'TV', 'is_active' => false]);

        $this->assertSame(1, $this->service->paginate(['search' => 'Đào'])->total());
        $this->assertSame(1, $this->service->paginate(['is_active' => false])->total());
        $this->assertSame(2, $this->service->paginate([])->total());
    }

    public function test_cannot_delete_department_with_support_types(): void
    {
        $department = SupportDepartment::create(['name' => 'Phòng Đào Tạo', 'code' => 'DT', 'is_active' => true]);
        SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $department->id, 'is_active' => true]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Không thể xóa phòng ban vì vẫn có loại hỗ trợ liên kết.');

        $this->service->delete($department);
    }

    public function test_delete_department_without_links(): void
    {
        $department = SupportDepartment::create(['name' => 'Phòng Đào Tạo', 'code' => 'DT', 'is_active' => true]);

        $this->service->delete($department);

        $this->assertDatabaseMissing('support_departments', ['id' => $department->id]);
    }
}
