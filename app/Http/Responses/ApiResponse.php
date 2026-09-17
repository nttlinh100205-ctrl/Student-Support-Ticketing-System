<?php

namespace App\Http\Responses;

class ApiResponse
{
    /**
     * Format response thành công theo chuẩn API_CONTRACT.md Mục 5.2.
     * Dùng chung cho toàn bộ 5 module — không tự đặt tên field khác.
     */
    public static function success($data = null, ?string $message = null, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * Format response lỗi theo chuẩn API_CONTRACT.md Mục 5.2.
     * $errors chỉ truyền khi lỗi validate (422).
     */
    public static function error(string $message, int $status = 400, ?array $errors = null)
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
