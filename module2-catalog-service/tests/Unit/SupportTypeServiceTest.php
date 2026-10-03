<?php

namespace Tests\Unit;

use App\Models\SupportDepartment;
use App\Models\SupportType;
use App\Services\SupportTypeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SupportTypeServiceTest extends TestCase
{
    use RefreshDatabase;

    private SupportTypeService $service;

    private SupportDepartment $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SupportTypeService;
        $this->department = SupportDepartment::create(['name' => 'Phòng Đào Tạo', 'code' => 'DT', 'is_active' => true]);
    }

    public function test_create_defaults_to_active_and_loads_department(): void
    {
        $supportType = $this->service->create([
            'name' => 'Cấp bảng điểm',
            'code' => 'BD',
            'department_id' => $this->department->id,
            'sla_days' => 5,
        ]);

        $this->assertTrue($supportType->is_active);
        $this->assertTrue($supportType->relationLoaded('department'));
        $this->assertSame('DT', $supportType->department->code);
    }

    public function test_paginate_filters_by_department(): void
    {
        $other = SupportDepartment::create(['name' => 'Thư viện', 'code' => 'TV', 'is_active' => true]);
        SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $this->department->id]);
        SupportType::create(['name' => 'Thẻ thư viện', 'code' => 'TTV', 'department_id' => $other->id]);

        $this->assertSame(1, $this->service->paginate(['department_id' => $other->id])->total());
        $this->assertSame(2, $this->service->paginate([])->total());
    }

    public function test_cannot_delete_support_type_used_by_request(): void
    {
        $supportType = SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $this->department->id]);

        // Bảng requests thuộc Module 3; tạo tạm để giả lập đã có yêu cầu sử dụng.
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('support_type_id');
        });
        DB::table('requests')->insert(['department_id' => $this->department->id, 'support_type_id' => $supportType->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Không thể xóa loại hỗ trợ vì đã có yêu cầu sử dụng loại hỗ trợ này.');

        $this->service->delete($supportType);
    }

    public function test_delete_unused_support_type(): void
    {
        $supportType = SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $this->department->id]);

        $this->service->delete($supportType);

        $this->assertDatabaseMissing('support_types', ['id' => $supportType->id]);
    }
}
