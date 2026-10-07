<?php

namespace Tests\Unit;

use App\Models\SupportDepartment;
use App\Models\User;
use App\Services\DepartmentStaffService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DepartmentStaffServiceTest extends TestCase
{
    use RefreshDatabase;

    private DepartmentStaffService $service;

    private SupportDepartment $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DepartmentStaffService;
        $this->department = SupportDepartment::create(['name' => 'Phòng Đào Tạo', 'code' => 'DT', 'is_active' => true]);
    }

    public function test_assign_moves_user_into_department(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $assigned = $this->service->assign(999, $this->department, $user, 'department_head');

        $this->assertSame('department_head', $assigned->role);
        $this->assertSame($this->department->id, (int) $assigned->department_id);
        $this->assertTrue($assigned->relationLoaded('department'));
    }

    public function test_cannot_assign_yourself(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->expectException(AuthorizationException::class);

        $this->service->assign($user->id, $this->department, $user, 'staff');
    }

    public function test_cannot_assign_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Không thể gán tài khoản ADMIN vào phòng ban.');

        $this->service->assign(999, $this->department, $admin, 'staff');
    }

    public function test_can_keep_current_inactive_department_but_not_move_into_it(): void
    {
        $this->department->update(['is_active' => false]);
        $member = User::factory()->create(['role' => 'staff', 'department_id' => $this->department->id]);
        $outsider = User::factory()->create(['role' => 'student']);

        // Cán bộ đang thuộc phòng (đã ngừng hoạt động) vẫn được đổi vai trò tại chỗ.
        $this->assertSame('department_head', $this->service->assign(999, $this->department, $member, 'department_head')->role);

        $this->expectException(ValidationException::class);

        $this->service->assign(999, $this->department, $outsider, 'staff');
    }

    public function test_candidates_exclude_admin(): void
    {
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'student']);

        $this->assertSame(1, $this->service->paginateCandidates([])->total());
    }
}
