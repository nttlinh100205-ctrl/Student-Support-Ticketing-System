<?php

namespace Tests\Unit;

use App\Models\SupportDepartment;
use App\Models\SupportFaq;
use App\Models\SupportType;
use App\Services\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class CatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private CatalogService $service;

    private SupportDepartment $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CatalogService;
        $this->department = SupportDepartment::create(['name' => 'Phòng Đào Tạo', 'code' => 'DT', 'is_active' => true]);
    }

    public function test_support_types_hide_inactive_type_and_inactive_department(): void
    {
        $closed = SupportDepartment::create(['name' => 'Thư viện', 'code' => 'TV', 'is_active' => false]);
        SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $this->department->id, 'is_active' => true]);
        SupportType::create(['name' => 'Phúc khảo', 'code' => 'PK', 'department_id' => $this->department->id, 'is_active' => false]);
        SupportType::create(['name' => 'Thẻ thư viện', 'code' => 'TTV', 'department_id' => $closed->id, 'is_active' => true]);

        $this->assertSame(1, $this->service->paginateSupportTypes([])->total());
    }

    public function test_support_type_faqs_include_department_and_own_faqs_only(): void
    {
        $supportType = SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $this->department->id]);
        $other = SupportType::create(['name' => 'Phúc khảo', 'code' => 'PK', 'department_id' => $this->department->id]);
        $faq = fn (array $data) => SupportFaq::create($data + [
            'department_id' => $this->department->id, 'answer' => 'Trả lời', 'is_active' => true,
        ]);

        $faq(['question' => 'Chung của phòng']);
        $faq(['question' => 'Riêng bảng điểm', 'support_type_id' => $supportType->id]);
        $faq(['question' => 'Riêng phúc khảo', 'support_type_id' => $other->id]);

        $this->assertSame(2, $this->service->paginateSupportTypeFaqs($supportType)->total());
    }

    public function test_support_type_of_inactive_department_is_not_available(): void
    {
        $this->department->update(['is_active' => false]);
        $supportType = SupportType::create(['name' => 'Bảng điểm', 'code' => 'BD', 'department_id' => $this->department->id, 'is_active' => true]);

        $this->expectException(NotFoundHttpException::class);

        $this->service->ensureAvailable($supportType);
    }
}
