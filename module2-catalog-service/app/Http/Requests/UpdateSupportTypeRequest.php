<?php

namespace App\Http\Requests;

/**
 * Sửa loại hỗ trợ dùng cùng quy tắc với thêm mới;
 * rule unique của code đã tự bỏ qua loại hỗ trợ đang sửa.
 */
class UpdateSupportTypeRequest extends StoreSupportTypeRequest {}
