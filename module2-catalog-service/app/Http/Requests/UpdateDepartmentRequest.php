<?php

namespace App\Http\Requests;

/**
 * Sửa phòng ban dùng cùng quy tắc với thêm mới;
 * rule unique của code đã tự bỏ qua phòng ban đang sửa.
 */
class UpdateDepartmentRequest extends StoreDepartmentRequest {}
