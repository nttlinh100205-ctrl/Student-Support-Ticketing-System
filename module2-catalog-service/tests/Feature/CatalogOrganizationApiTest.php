<?php

namespace Tests\Feature;

use App\Models\SupportDepartment;
use App\Models\SupportType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogOrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): void
    {
        $this->withHeaders([
            'X-User-Id' => '1',
            'X-User-Role' => 'admin',
        ]);
    }

    /**
     * Bảng requests thuộc Module 3; giả lập database dùng chung.
     */
    private function createRequestRow(int $departmentId, int $supportTypeId): void
    {
        if (! Schema::hasTable('requests')) {
            Schema::create('requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('support_type_id');
            });
        }

        DB::table('requests')->insert([
            'department_id' => $departmentId,
            'support_type_id' => $supportTypeId,
        ]);
    }

    public function test_admin_can_crud_department_and_support_type(): void
    {
        $this->loginAsAdmin();

        // 1. Tạo phòng ban
        $departmentResponse = $this->postJson('/api/v1/admin/departments', [
            'name' => 'Phòng Đào Tạo',
            'code' => 'DT',
            'description' => 'Phụ trách đào tạo',
            'is_active' => true,
        ]);

        $departmentResponse
            ->assertCreated()
            ->assertJsonPath('data.code', 'DT');

        $departmentId = $departmentResponse->json('data.id');

        // 2. Xem phòng ban
        $this->getJson("/api/v1/admin/departments/{$departmentId}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Phòng Đào Tạo');

        // 3. Cập nhật phòng ban
        $this->putJson("/api/v1/admin/departments/{$departmentId}", [
            'name' => 'Phòng Đào tạo mới',
            'code' => 'DT',
            'description' => 'Đã cập nhật',
            'is_active' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Phòng Đào tạo mới');

        // 4. Tạo loại hỗ trợ
        $supportTypeResponse = $this->postJson('/api/v1/admin/support-types', [
            'name' => 'Đăng ký học phần',
            'code' => 'DKHP',
            'description' => 'Hỗ trợ đăng ký học phần',
            'department_id' => $departmentId,
            'is_active' => true,
        ]);

        $supportTypeResponse
            ->assertCreated()
            ->assertJsonPath('data.code', 'DKHP');

        $supportTypeId = $supportTypeResponse->json('data.id');

        // 5. Cập nhật loại hỗ trợ
        $this->putJson("/api/v1/admin/support-types/{$supportTypeId}", [
            'name' => 'Đăng ký và điều chỉnh học phần',
            'code' => 'DKHP',
            'description' => 'Đã cập nhật',
            'department_id' => $departmentId,
            'is_active' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Đăng ký và điều chỉnh học phần');

        // 6. Xóa loại hỗ trợ trước
        $this->deleteJson("/api/v1/admin/support-types/{$supportTypeId}")
            ->assertOk();

        $this->assertDatabaseMissing('support_types', ['id' => $supportTypeId]);

        // 7. Sau đó mới xóa phòng ban
        $this->deleteJson("/api/v1/admin/departments/{$departmentId}")
            ->assertOk();

        $this->assertDatabaseMissing('support_departments', ['id' => $departmentId]);
    }

    public function test_cannot_delete_support_type_used_by_request(): void
    {
        $this->loginAsAdmin();

        $department = SupportDepartment::create([
            'name' => 'Phòng Công tác Sinh viên',
            'code' => 'CTSV',
            'is_active' => true,
        ]);

        $supportType = SupportType::create([
            'name' => 'Xác nhận sinh viên',
            'code' => 'XNSV',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->createRequestRow($department->id, $supportType->id);

        $this->deleteJson("/api/v1/admin/support-types/{$supportType->id}")
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Không thể xóa loại hỗ trợ vì đã có yêu cầu sử dụng loại hỗ trợ này.',
            ]);

        $this->assertDatabaseHas('support_types', ['id' => $supportType->id]);
    }

    public function test_cannot_delete_department_with_support_type(): void
    {
        $this->loginAsAdmin();

        $department = SupportDepartment::create([
            'name' => 'Phòng Đào Tạo',
            'code' => 'DT',
            'is_active' => true,
        ]);

        SupportType::create([
            'name' => 'Bảng điểm',
            'code' => 'BD',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->deleteJson("/api/v1/admin/departments/{$department->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('support_departments', ['id' => $department->id]);
    }

    public function test_cannot_delete_department_used_by_request(): void
    {
        $this->loginAsAdmin();

        $department = SupportDepartment::create([
            'name' => 'Phòng Đào Tạo',
            'code' => 'DT',
            'is_active' => true,
        ]);

        // support_type_id là tham chiếu mềm, dùng ID giả
        // để không bị chặn ở rule "còn loại hỗ trợ".
        $this->createRequestRow($department->id, 999);

        $this->deleteJson("/api/v1/admin/departments/{$department->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('support_departments', ['id' => $department->id]);
    }

    public function test_non_admin_cannot_access_catalog_admin_api(): void
    {
        $this->withHeaders([
            'X-User-Id' => '2',
            'X-User-Role' => 'student',
        ])->getJson('/api/v1/admin/departments')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập chức năng này.',
            ]);
    }

    public function test_request_without_auth_headers_is_rejected(): void
    {
        $this->getJson('/api/v1/admin/departments')
            ->assertStatus(401)
            ->assertJson(['success' => false]);

        $this->getJson('/api/v1/catalog/departments')
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }
}
