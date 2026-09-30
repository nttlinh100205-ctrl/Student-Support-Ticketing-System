<?php

namespace Tests\Feature;

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VerifyJwt;
use App\Models\Department;
use App\Models\DepartmentStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyJwt::class,
            RoleMiddleware::class,
        ]);
    }

    public function test_can_get_department_list(): void
    {
        Department::create([
            'name' => 'Phòng Hỗ trợ Sinh viên',
            'code' => 'SUPPORT',
            'description' => 'Phòng hỗ trợ sinh viên',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/departments');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.code',
                'SUPPORT'
            );
    }

    public function test_can_create_department(): void
    {
        $response = $this->postJson('/api/departments', [
            'name' => 'Phòng Công tác Sinh viên',
            'code' => 'CTSV',
            'description' => 'Phòng công tác sinh viên',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.name',
                'Phòng Công tác Sinh viên'
            )
            ->assertJsonPath(
                'data.code',
                'CTSV'
            );

        $this->assertDatabaseHas(
            'support_departments',
            [
                'code' => 'CTSV',
                'is_active' => 1,
            ]
        );
    }

    public function test_can_show_department(): void
    {
        $department = Department::create([
            'name' => 'Phòng Hỗ trợ',
            'code' => 'SUPPORT',
            'description' => 'Mô tả',
            'is_active' => true,
        ]);

        $response = $this->getJson(
            '/api/departments/' . $department->id
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.id',
                $department->id
            )
            ->assertJsonPath(
                'data.code',
                'SUPPORT'
            );
    }

    public function test_can_get_department_staff(): void
    {
        $department = Department::create([
            'name' => 'Phòng Hỗ trợ',
            'code' => 'SUPPORT',
            'description' => null,
            'is_active' => true,
        ]);

        DepartmentStaff::create([
            'department_id' => $department->id,
            'user_id' => 3,
            'role' => 'staff',
        ]);

        $response = $this->getJson(
            '/api/departments/' . $department->id . '/staff'
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.user_id',
                3
            )
            ->assertJsonPath(
                'data.0.role',
                'staff'
            );
    }
}