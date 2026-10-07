<?php

namespace App\Http\Requests;

/**
 * Sửa trường biểu mẫu dùng cùng quy tắc với thêm mới;
 * rule unique của field_key đã tự bỏ qua trường đang sửa.
 */
class UpdateSupportTypeFieldRequest extends StoreSupportTypeFieldRequest {}
