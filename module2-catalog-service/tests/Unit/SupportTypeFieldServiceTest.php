<?php

namespace Tests\Unit;

use App\Models\SupportDepartment;
use App\Models\SupportType;
use App\Services\SupportTypeFieldService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SupportTypeFieldServiceTest extends TestCase
{
    use RefreshDatabase;

    private SupportTypeFieldService $service;

    private SupportType $supportType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SupportTypeFieldService;

        $department = SupportDepartment::create(['name' => 'Phòng Đào Tạo', 'code' => 'DT', 'is_active' => true]);
        $this->supportType = SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $department->id]);
    }

    public function test_create_applies_defaults(): void
    {
        $field = $this->service->create($this->supportType, [
            'field_key' => 'student_code',
            'label' => 'Mã sinh viên',
            'field_type' => 'text',
            'options' => null,
        ]);

        $this->assertFalse($field->is_required);
        $this->assertTrue($field->is_active);
        $this->assertSame(0, $field->sort_order);
    }

    public function test_list_filters_by_status(): void
    {
        $this->service->create($this->supportType, ['field_key' => 'a', 'label' => 'A', 'field_type' => 'text']);
        $this->service->create($this->supportType, ['field_key' => 'b', 'label' => 'B', 'field_type' => 'text', 'is_active' => false]);

        $this->assertCount(1, $this->service->list($this->supportType, ['is_active' => false]));
        $this->assertCount(2, $this->service->list($this->supportType, []));
    }

    public function test_update_status_returns_fresh_field(): void
    {
        $field = $this->service->create($this->supportType, ['field_key' => 'a', 'label' => 'A', 'field_type' => 'text']);

        $this->assertFalse($this->service->updateStatus($field, false)->is_active);
    }

    public function test_field_of_other_support_type_is_rejected(): void
    {
        $other = SupportType::create(['name' => 'Phúc khảo', 'code' => 'PK', 'department_id' => $this->supportType->department_id]);
        $field = $this->service->create($other, ['field_key' => 'subject', 'label' => 'Môn học', 'field_type' => 'text']);

        $this->expectException(NotFoundHttpException::class);

        $this->service->ensureBelongsToType($this->supportType, $field);
    }
}
