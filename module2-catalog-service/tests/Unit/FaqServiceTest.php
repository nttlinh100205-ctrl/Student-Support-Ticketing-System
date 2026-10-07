<?php

namespace Tests\Unit;

use App\Models\SupportDepartment;
use App\Models\SupportType;
use App\Services\FaqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqServiceTest extends TestCase
{
    use RefreshDatabase;

    private FaqService $service;

    private SupportDepartment $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FaqService;
        $this->department = SupportDepartment::create(['name' => 'Phòng Đào Tạo', 'code' => 'DT', 'is_active' => true]);
    }

    public function test_create_applies_defaults_and_loads_relations(): void
    {
        $faq = $this->service->create([
            'department_id' => $this->department->id,
            'support_type_id' => null,
            'question' => 'Bao lâu thì nhận được bảng điểm?',
            'answer' => 'Khoảng 3 ngày làm việc.',
        ]);

        $this->assertTrue($faq->is_active);
        $this->assertSame(0, $faq->sort_order);
        $this->assertTrue($faq->relationLoaded('department'));
    }

    public function test_paginate_filters_by_support_type_and_search(): void
    {
        $supportType = SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $this->department->id]);
        $base = ['department_id' => $this->department->id, 'answer' => 'Trả lời'];

        $this->service->create($base + ['question' => 'Câu hỏi chung']);
        $this->service->create($base + ['question' => 'Bảng điểm cấp lại', 'support_type_id' => $supportType->id]);

        $this->assertSame(1, $this->service->paginate(['support_type_id' => $supportType->id])->total());
        $this->assertSame(1, $this->service->paginate(['search' => 'chung'])->total());
        $this->assertSame(2, $this->service->paginate([])->total());
    }

    public function test_update_status_hides_faq(): void
    {
        $faq = $this->service->create([
            'department_id' => $this->department->id,
            'question' => 'Câu hỏi',
            'answer' => 'Trả lời',
        ]);

        $this->assertFalse($this->service->updateStatus($faq, false)->is_active);
    }
}
