<?php

namespace Tests\Unit;

use App\Models\SupportDepartment;
use App\Models\SupportType;
use App\Services\FormValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private FormValidationService $service;

    private SupportType $supportType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FormValidationService;

        $department = SupportDepartment::create(['name' => 'Phòng Đào Tạo', 'code' => 'DT', 'is_active' => true]);
        $this->supportType = SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $department->id]);

        $this->supportType->fields()->createMany([
            ['field_key' => 'student_code', 'label' => 'Mã sinh viên', 'field_type' => 'text', 'is_required' => true],
            ['field_key' => 'semester', 'label' => 'Học kỳ', 'field_type' => 'select', 'options' => ['HK1', 'HK2']],
            ['field_key' => 'proof', 'label' => 'Minh chứng', 'field_type' => 'file'],
        ]);
    }

    public function test_returns_only_form_fields(): void
    {
        $values = $this->service->validate($this->supportType, ['values' => [
            'student_code' => 'SV001',
            'proof' => 'xac-nhan.pdf',
            'unknown' => 'bị bỏ',
        ]]);

        $this->assertSame(['student_code' => 'SV001', 'semester' => null, 'proof' => 'xac-nhan.pdf'], $values);
    }

    public function test_reports_errors_by_field_label(): void
    {
        try {
            $this->service->validate($this->supportType, ['values' => ['semester' => 'HK3']]);
            $this->fail('Phải ném ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame('Vui lòng nhập Mã sinh viên.', $e->errors()['values.student_code'][0]);
            $this->assertSame('Học kỳ không nằm trong danh sách lựa chọn.', $e->errors()['values.semester'][0]);
        }
    }
}
