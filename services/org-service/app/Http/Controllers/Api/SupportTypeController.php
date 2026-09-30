<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SupportType;
use Illuminate\Http\Request;

class SupportTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportType::query()
            ->where('is_active', true)
            ->orderBy('id');

        if ($request->filled('department_id')) {
            $query->where(
                'department_id',
                (int) $request->query('department_id')
            );
        }

        return ApiResponse::success(
            $query->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => [
                'required',
                'integer',
                'exists:support_departments,id',
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $supportType = SupportType::create(
            $data + [
                'is_active' => true,
            ]
        );

        return ApiResponse::success(
            $supportType,
            'Tạo loại hỗ trợ thành công.',
            201
        );
    }
}