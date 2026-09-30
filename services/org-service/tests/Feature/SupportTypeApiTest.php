<?php

namespace Tests\Feature;

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VerifyJwt;
use App\Models\Department;
use App\Models\SupportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTypeApiTest extends TestCase
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

    public function test_can_get_support_type_list(): void
    {
        $department = Department::create([
            'name' => 'Phòng Hỗ trợ',
            'code' => 'SUPPORT',
            'description' => null,
            'is_active' => true,
        ]);

        SupportType::create([
            'department_id' => $department->id,
            'name' => 'Xác nhận sinh viên',
            'description' => 'Các yêu cầu xác nhận',
            'is_active' => true,
        ]);

        $response = $this->getJson(
            '/api/support-types'
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.name',
                'Xác nhận sinh viên'
            );
    }

    public function test_can_filter_support_types_by_department(): void
    {
        $department1 = Department::create([
            'name' => 'Phòng Hỗ trợ 1',
            'code' => 'SUP1',
            'description' => null,
            'is_active' => true,
        ]);

        $department2 = Department::create([
            'name' => 'Phòng Hỗ trợ 2',
            'code' => 'SUP2',
            'description' => null,
            'is_active' => true,
        ]);

        SupportType::create([
            'department_id' => $department1->id,
            'name' => 'Loại 1',
            'description' => null,
            'is_active' => true,
        ]);

        SupportType::create([
            'department_id' => $department2->id,
            'name' => 'Loại 2',
            'description' => null,
            'is_active' => true,
        ]);

        $response = $this->getJson(
            '/api/support-types?department_id='
            . $department1->id
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.name',
                'Loại 1'
            );
    }

    public function test_can_create_support_type(): void
    {
        $department = Department::create([
            'name' => 'Phòng Hỗ trợ',
            'code' => 'SUPPORT',
            'description' => null,
            'is_active' => true,
        ]);

        $response = $this->postJson(
            '/api/support-types',
            [
                'department_id' => $department->id,
                'name' => 'Hỗ trợ học bổng',
                'description' => 'Các yêu cầu liên quan học bổng',
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.department_id',
                $department->id
            )
            ->assertJsonPath(
                'data.name',
                'Hỗ trợ học bổng'
            );

        $this->assertDatabaseHas(
            'support_types',
            [
                'department_id' => $department->id,
                'name' => 'Hỗ trợ học bổng',
                'is_active' => 1,
            ]
        );
    }
}