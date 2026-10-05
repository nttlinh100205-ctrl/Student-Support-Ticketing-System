<?php

use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\DepartmentStaffController;
use App\Http\Controllers\Api\SupportDepartmentController;
use App\Http\Controllers\Api\SupportFaqController;
use App\Http\Controllers\Api\SupportTypeController;
use App\Http\Controllers\Api\SupportTypeFieldController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MODULE 2 - DANH MỤC VÀ TỔ CHỨC
|--------------------------------------------------------------------------
|
| Xác thực bằng header giả X-User-Id / X-User-Role (giống Module 3).
| Khi Module 1 có JWT thật, chỉ cần đổi middleware 'auth.fake'.
|
*/

/*
|--------------------------------------------------------------------------
| QUẢN TRỊ - chỉ ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.fake', 'role:admin'])
    ->group(function () {

        // Phòng ban
        Route::get('/departments', [SupportDepartmentController::class, 'index']);
        Route::post('/departments', [SupportDepartmentController::class, 'store']);
        Route::get('/departments/{department}', [SupportDepartmentController::class, 'show']);
        Route::put('/departments/{department}', [SupportDepartmentController::class, 'update']);
        Route::delete('/departments/{department}', [SupportDepartmentController::class, 'destroy']);

        // Cán bộ theo phòng ban
        Route::get('/departments/{department}/staff', [SupportDepartmentController::class, 'staff']);
        Route::put('/departments/{department}/staff/{user}', [DepartmentStaffController::class, 'assign']);
        Route::get('/staff-candidates', [DepartmentStaffController::class, 'candidates']);

        // Loại hỗ trợ (bao gồm SLA - sla_days)
        Route::get('/support-types', [SupportTypeController::class, 'index']);
        Route::post('/support-types', [SupportTypeController::class, 'store']);
        Route::get('/support-types/{supportType}', [SupportTypeController::class, 'show']);
        Route::put('/support-types/{supportType}', [SupportTypeController::class, 'update']);
        Route::delete('/support-types/{supportType}', [SupportTypeController::class, 'destroy']);

        // Biểu mẫu theo loại hỗ trợ
        Route::prefix('/support-types/{supportType}/fields')->group(function () {
            Route::get('/', [SupportTypeFieldController::class, 'index']);
            Route::post('/', [SupportTypeFieldController::class, 'store']);
            Route::get('/{field}', [SupportTypeFieldController::class, 'show']);
            Route::put('/{field}', [SupportTypeFieldController::class, 'update']);
            Route::put('/{field}/status', [SupportTypeFieldController::class, 'updateStatus']);
        });

        // Câu hỏi thường gặp
        Route::get('/faqs', [SupportFaqController::class, 'index']);
        Route::post('/faqs', [SupportFaqController::class, 'store']);
        Route::get('/faqs/{faq}', [SupportFaqController::class, 'show']);
        Route::put('/faqs/{faq}', [SupportFaqController::class, 'update']);
        Route::put('/faqs/{faq}/status', [SupportFaqController::class, 'updateStatus']);
    });

/*
|--------------------------------------------------------------------------
| TRA CỨU DANH MỤC - mọi tài khoản đã xác thực
|--------------------------------------------------------------------------
|
| Sinh viên xem trước khi gửi yêu cầu; Module 3 gọi để lấy SLA,
| biểu mẫu và kiểm tra dữ liệu biểu mẫu.
|
*/
Route::middleware('auth.fake')
    ->prefix('catalog')
    ->group(function () {
        Route::get('/departments', [CatalogController::class, 'departments']);
        Route::get('/departments/{department}/faqs', [CatalogController::class, 'departmentFaqs']);
        Route::get('/support-types', [CatalogController::class, 'supportTypes']);
        Route::get('/support-types/{supportType}/form', [CatalogController::class, 'form']);
        Route::get('/support-types/{supportType}/faqs', [CatalogController::class, 'faqs']);
        Route::post('/support-types/{supportType}/validate', [CatalogController::class, 'validateForm']);
    });
