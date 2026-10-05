<?php

namespace Tests\Feature;

use App\Models\SupportDepartment;
use App\Models\SupportFaq;
use App\Models\SupportType;
use App\Models\SupportTypeField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Phần mở rộng Module 2: SLA, biểu mẫu theo loại, FAQ, gán cán bộ.
 */
class Module2ExtensionApiTest extends TestCase
{
    use RefreshDatabase;

    private SupportDepartment $department;

    private SupportType $supportType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = SupportDepartment::create([
            'name' => 'Phòng Đào Tạo',
            'code' => 'DT',
            'is_active' => true,
        ]);

        $this->supportType = SupportType::create([
            'name' => 'Xin bảng điểm',
            'code' => 'BD',
            'department_id' => $this->department->id,
            'is_active' => true,
            'sla_days' => 3,
        ]);
    }

    private function asAdmin(): static
    {
        return $this->withHeaders(['X-User-Id' => '999', 'X-User-Role' => 'admin']);
    }

    private function asStudent(): static
    {
        return $this->withHeaders(['X-User-Id' => '2', 'X-User-Role' => 'student']);
    }

    private function addField(array $data): SupportTypeField
    {
        return $this->supportType->fields()->create(array_merge([
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 0,
        ], $data));
    }

    /* ---------- SLA ---------- */

    public function test_admin_can_set_and_clear_sla_days(): void
    {
        $payload = [
            'name' => 'Xin bảng điểm',
            'code' => 'BD',
            'department_id' => $this->department->id,
        ];

        $this->asAdmin()
            ->putJson("/api/support-types/{$this->supportType->id}", $payload + ['sla_days' => 7])
            ->assertOk()
            ->assertJsonPath('data.sla_days', 7);

        $this->asAdmin()
            ->putJson("/api/support-types/{$this->supportType->id}", $payload + ['sla_days' => null])
            ->assertOk()
            ->assertJsonPath('data.sla_days', null);

        $this->asAdmin()
            ->postJson('/api/support-types', [
                'name' => 'Phúc khảo',
                'code' => 'PK',
                'department_id' => $this->department->id,
                'sla_days' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('data.sla_days', 10);
    }

    public function test_sla_days_must_be_between_1_and_365(): void
    {
        $this->asAdmin()
            ->putJson("/api/support-types/{$this->supportType->id}", [
                'name' => 'Xin bảng điểm',
                'code' => 'BD',
                'department_id' => $this->department->id,
                'sla_days' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('sla_days');
    }

    public function test_catalog_returns_sla_for_module_3(): void
    {
        $this->asStudent()
            ->getJson("/api/catalog/support-types/{$this->supportType->id}/form")
            ->assertOk()
            ->assertJsonPath('data.support_type.sla_days', 3);
    }

    /* ---------- Biểu mẫu ---------- */

    public function test_admin_can_manage_form_fields(): void
    {
        $base = "/api/support-types/{$this->supportType->id}/fields";

        $fieldId = $this->asAdmin()
            ->postJson($base, [
                'field_key' => 'semester',
                'label' => 'Học kỳ',
                'field_type' => 'select',
                'is_required' => true,
                'options' => ['Học kỳ 1', 'Học kỳ 2'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.options', ['Học kỳ 1', 'Học kỳ 2'])
            ->json('data.id');

        // Mã trường không được trùng trong cùng loại hỗ trợ.
        $this->asAdmin()
            ->postJson($base, ['field_key' => 'semester', 'label' => 'Khác', 'field_type' => 'text'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('field_key');

        // Kiểu select bắt buộc có lựa chọn.
        $this->asAdmin()
            ->postJson($base, ['field_key' => 'subject', 'label' => 'Môn học', 'field_type' => 'select'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('options');

        // Sửa trường: giữ nguyên mã không bị báo trùng; đổi sang kiểu khác select thì bỏ options.
        $this->asAdmin()
            ->putJson("{$base}/{$fieldId}", [
                'field_key' => 'semester',
                'label' => 'Học kỳ đăng ký',
                'field_type' => 'text',
                'options' => ['Học kỳ 1'],
            ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Học kỳ đăng ký')
            ->assertJsonPath('data.options', null);

        $this->asAdmin()
            ->putJson("{$base}/{$fieldId}/status", ['is_active' => false])

            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        // Trường đã tắt không xuất hiện trong biểu mẫu của sinh viên.
        $this->asStudent()
            ->getJson("/api/catalog/support-types/{$this->supportType->id}/form")
            ->assertOk()
            ->assertJsonCount(0, 'data.fields');
    }

    public function test_field_of_other_support_type_is_not_accessible(): void
    {
        $other = SupportType::create([
            'name' => 'Phúc khảo',
            'code' => 'PK',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $field = $other->fields()->create([
            'field_key' => 'subject',
            'label' => 'Môn học',
            'field_type' => 'text',
        ]);

        $this->asAdmin()
            ->getJson("/api/support-types/{$this->supportType->id}/fields/{$field->id}")
            ->assertNotFound();
    }

    public function test_validate_form_reports_missing_and_invalid_values(): void
    {
        $this->addField(['field_key' => 'student_code', 'label' => 'Mã sinh viên', 'field_type' => 'text', 'is_required' => true]);
        $this->addField(['field_key' => 'copies', 'label' => 'Số bản', 'field_type' => 'number', 'is_required' => true]);
        $this->addField(['field_key' => 'semester', 'label' => 'Học kỳ', 'field_type' => 'select', 'options' => ['Học kỳ 1', 'Học kỳ 2']]);
        $this->addField(['field_key' => 'proof', 'label' => 'Giấy xác nhận', 'field_type' => 'file', 'is_required' => true]);

        $url = "/api/catalog/support-types/{$this->supportType->id}/validate";

        $this->asStudent()
            ->postJson($url, ['values' => ['copies' => 'abc', 'semester' => 'Học kỳ 9']])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'values.student_code',
                'values.copies',
                'values.semester',
                'values.proof',
            ])
            ->assertJsonValidationErrors([
                'values.student_code' => 'Vui lòng nhập Mã sinh viên.',
            ]);

        $this->asStudent()
            ->post($url, [
                'values' => [
                    'student_code' => 'SV001',
                    'copies' => '2',
                    'semester' => 'Học kỳ 1',
                    'proof' => UploadedFile::fake()->create('xac-nhan.pdf', 100),
                    'unknown' => 'bị bỏ qua',
                ],
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.sla_days', 3)
            ->assertJsonPath('data.values.student_code', 'SV001')
            ->assertJsonPath('data.values.proof', 'xac-nhan.pdf')
            ->assertJsonMissingPath('data.values.unknown');
    }

    public function test_inactive_support_type_is_hidden_from_catalog(): void
    {
        $this->supportType->update(['is_active' => false]);

        $this->asStudent()
            ->getJson("/api/catalog/support-types/{$this->supportType->id}/form")
            ->assertNotFound();

        $this->asStudent()
            ->getJson('/api/catalog/support-types')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
    }

    /* ---------- FAQ ---------- */

    public function test_admin_can_manage_faqs(): void
    {
        $faqId = $this->asAdmin()
            ->postJson('/api/faqs', [
                'department_id' => $this->department->id,
                'support_type_id' => $this->supportType->id,
                'question' => 'Bao lâu thì nhận được bảng điểm?',
                'answer' => 'Khoảng 3 ngày làm việc.',
            ])
            ->assertCreated()
            ->json('data.id');

        // Sửa FAQ: bỏ chọn loại hỗ trợ thì thành FAQ chung của phòng ban.
        $this->asAdmin()
            ->putJson("/api/faqs/{$faqId}", [
                'department_id' => $this->department->id,
                'question' => 'Bao lâu thì nhận được bảng điểm?',
                'answer' => 'Khoảng 5 ngày làm việc.',
            ])
            ->assertOk()
            ->assertJsonPath('data.answer', 'Khoảng 5 ngày làm việc.')
            ->assertJsonPath('data.support_type_id', null);

        $this->asAdmin()
            ->putJson("/api/faqs/{$faqId}/status", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->asAdmin()
            ->getJson('/api/faqs?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_faq_support_type_must_belong_to_department(): void
    {
        $other = SupportDepartment::create(['name' => 'Phòng CTSV', 'code' => 'CTSV', 'is_active' => true]);

        $this->asAdmin()
            ->postJson('/api/faqs', [
                'department_id' => $other->id,
                'support_type_id' => $this->supportType->id,
                'question' => 'Câu hỏi',
                'answer' => 'Trả lời',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('support_type_id');
    }

    public function test_student_sees_department_and_type_faqs(): void
    {
        $other = SupportType::create([
            'name' => 'Phúc khảo',
            'code' => 'PK',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $faq = fn (array $data) => SupportFaq::create(array_merge([
            'department_id' => $this->department->id,
            'answer' => 'Trả lời',
            'is_active' => true,
        ], $data));

        $faq(['question' => 'Chung của phòng']);
        $faq(['question' => 'Riêng bảng điểm', 'support_type_id' => $this->supportType->id]);
        $faq(['question' => 'Riêng phúc khảo', 'support_type_id' => $other->id]);
        $faq(['question' => 'Đã ẩn', 'is_active' => false]);

        // Theo loại: FAQ chung của phòng + FAQ riêng của loại đó.
        $this->asStudent()
            ->getJson("/api/catalog/support-types/{$this->supportType->id}/faqs")
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonMissing(['question' => 'Riêng phúc khảo'])
            ->assertJsonMissing(['question' => 'Đã ẩn']);

        // Theo phòng ban: tất cả FAQ đang hiển thị.
        $this->asStudent()
            ->getJson("/api/catalog/departments/{$this->department->id}/faqs")
            ->assertOk()
            ->assertJsonCount(3, 'data.data');
    }

    /* ---------- Cán bộ theo phòng ban ---------- */

    public function test_admin_can_assign_staff_to_department(): void
    {
        $user = User::factory()->create(['role' => 'student', 'status' => 'ACTIVE']);

        $this->asAdmin()
            ->putJson("/api/departments/{$this->department->id}/staff/{$user->id}", [
                'role' => 'staff',
            ])
            ->assertOk()
            ->assertJsonPath('data.department_id', $this->department->id);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);

        $this->asAdmin()
            ->getJson("/api/departments/{$this->department->id}/staff")
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

    }

    public function test_cannot_assign_staff_to_inactive_department(): void
    {
        $user = User::factory()->create(['role' => 'student', 'status' => 'ACTIVE']);
        $this->department->update(['is_active' => false]);

        $this->asAdmin()
            ->putJson("/api/departments/{$this->department->id}/staff/{$user->id}", [
                'role' => 'staff',
            ])
            ->assertStatus(409);
    }

    public function test_admin_account_cannot_be_assigned(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ACTIVE']);

        $this->asAdmin()
            ->putJson("/api/departments/{$this->department->id}/staff/{$admin->id}", [
                'role' => 'staff',
            ])
            ->assertStatus(409);

        $this->asAdmin()
            ->getJson('/api/staff-candidates')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
    }
}
