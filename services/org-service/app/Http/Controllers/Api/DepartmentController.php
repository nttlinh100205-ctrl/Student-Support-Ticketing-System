<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Department;
use App\Models\DepartmentStaff;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return ApiResponse::success(
            Department::query()
                ->orderBy('id')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:support_departments,code',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $department = Department::create(
            $data + [
                'is_active' => true,
            ]
        );

        return ApiResponse::success(
            $department,
            'Tạo phòng ban thành công.',
            201
        );
    }

    public function show(Department $department)
    {
        return ApiResponse::success(
            $department
        );
    }

    public function staff(Department $department)
    {
        return ApiResponse::success(
            DepartmentStaff::query()
                ->where(
                    'department_id',
                    $department->id
                )
                ->orderBy('user_id')
                ->get()
        );
    }
}