<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MODULE 2 - GIAO DIỆN
|--------------------------------------------------------------------------
|
| Các trang chỉ trả về view; dữ liệu lấy qua API có kiểm tra quyền.
|
*/

require __DIR__.'/account.php';

Route::middleware('account.auth')->group(function () {
    Route::redirect('/', '/catalog');

    // Sinh viên tra cứu loại hỗ trợ, giấy tờ cần chuẩn bị và FAQ trước khi gửi.
    Route::view('/catalog', 'catalog.index')->name('catalog.index');

    // Quản trị danh mục (ADMIN).
    Route::view('/admin/departments', 'admin.departments')->name('admin.departments');
    Route::view('/admin/support-types', 'admin.support-types')->name('admin.support-types');
    Route::view('/admin/support-type-fields', 'admin.support-type-fields')->name('admin.support-type-fields');
    Route::view('/admin/department-staff', 'admin.department-staff')->name('admin.department-staff');
    Route::view('/admin/faqs', 'admin.faqs')->name('admin.faqs');

});
