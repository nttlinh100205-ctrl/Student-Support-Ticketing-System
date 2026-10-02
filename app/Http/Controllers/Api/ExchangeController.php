<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExchangeController extends Controller
{
    /**
     * Đọc dữ liệu từ exchanges.json
     */
    private function getData()
    {
        $path = 'mock_data/exchanges.json';

        if (!Storage::exists($path)) {
            Storage::put(
                $path,
                json_encode(
                    [],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );
        }

        $data = json_decode(
            Storage::get($path),
            true
        );

        return $data ?? [];
    }

    /**
     * Lưu dữ liệu vào exchanges.json
     */
    private function saveData($data)
    {
        Storage::put(
            'mock_data/exchanges.json',
            json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * GET /api/exchanges
     *
     * Có thể sử dụng:
     *
     * /api/exchanges
     *
     * /api/exchanges?request_id=1
     *
     * /api/exchanges?request_id=1&user_id=101&with_user_id=301
     */
    public function index(Request $request)
    {
        $data = $this->getData();

        /*
        |--------------------------------------------------------------------------
        | Lọc theo request_id
        |--------------------------------------------------------------------------
        */

        if ($request->filled('request_id')) {

            $requestId = (int) $request->request_id;

            $data = array_values(
                array_filter($data, function ($item) use ($requestId) {
                    return isset($item['request_id'])
                        && (int) $item['request_id'] === $requestId;
                })
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc cuộc trò chuyện giữa 2 người
        |--------------------------------------------------------------------------
        |
        | Ví dụ:
        |
        | user_id = 101
        | with_user_id = 301
        |
        | Sẽ lấy cả:
        |
        | 101 -> 301
        | 301 -> 101
        |
        */

        if (
            $request->filled('user_id')
            && $request->filled('with_user_id')
        ) {

            $userId = (int) $request->user_id;
            $withUserId = (int) $request->with_user_id;

            $data = array_values(
                array_filter($data, function ($item) use ($userId, $withUserId) {

                    $senderId = isset($item['sender_id'])
                        ? (int) $item['sender_id']
                        : null;

                    $receiverId = isset($item['receiver_id'])
                        ? (int) $item['receiver_id']
                        : null;

                    return (
                        $senderId === $userId
                        && $receiverId === $withUserId
                    )
                    ||
                    (
                        $senderId === $withUserId
                        && $receiverId === $userId
                    );
                })
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sắp xếp theo thời gian
        |--------------------------------------------------------------------------
        */

        usort($data, function ($a, $b) {

            return strcmp(
                $a['created_at'] ?? '',
                $b['created_at'] ?? ''
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách trao đổi thành công',
            'data' => $data
        ]);
    }

    /**
     * GET /api/exchanges/{id}
     */
    public function show($id)
    {
        $data = $this->getData();

        foreach ($data as $item) {

            if (
                isset($item['id'])
                && (int) $item['id'] === (int) $id
            ) {

                return response()->json([
                    'success' => true,
                    'message' => 'Lấy thông tin trao đổi thành công',
                    'data' => $item
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy trao đổi'
        ], 404);
    }

    /**
     * POST /api/exchanges
     *
     * Tạo tin nhắn mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'request_id' => 'required|integer',

            'sender_id' => 'required|integer',

            'sender_name' => 'required|string|max:255',

            'sender_role' => 'required|in:student,staff,manager',

            'receiver_id' => 'required|integer',

            'receiver_name' => 'required|string|max:255',

            'receiver_role' => 'required|in:student,staff,manager',

            'message' => 'required|string'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Không cho gửi tin nhắn cho chính mình
        |--------------------------------------------------------------------------
        */

        if (
            (int) $validated['sender_id']
            ===
            (int) $validated['receiver_id']
        ) {

            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi tin nhắn cho chính mình'
            ], 422);
        }

        $data = $this->getData();

        /*
        |--------------------------------------------------------------------------
        | Tạo ID mới
        |--------------------------------------------------------------------------
        */

        $newId = empty($data)
            ? 1
            : max(array_column($data, 'id')) + 1;

        $now = now()->format('Y-m-d H:i:s');

        /*
        |--------------------------------------------------------------------------
        | Tạo tin nhắn
        |--------------------------------------------------------------------------
        */

        $newExchange = [

            'id' => $newId,

            'request_id' => (int) $validated['request_id'],

            'sender_id' => (int) $validated['sender_id'],

            'sender_name' => $validated['sender_name'],

            'sender_role' => $validated['sender_role'],

            'receiver_id' => (int) $validated['receiver_id'],

            'receiver_name' => $validated['receiver_name'],

            'receiver_role' => $validated['receiver_role'],

            'message' => $validated['message'],

            'created_at' => $now,

            'updated_at' => $now
        ];

        $data[] = $newExchange;

        $this->saveData($data);

        return response()->json([
            'success' => true,
            'message' => 'Gửi tin nhắn thành công',
            'data' => $newExchange
        ], 201);
    }

    /**
     * PUT /api/exchanges/{id}
     *
     * Cập nhật nội dung tin nhắn
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'message' => 'required|string'
        ]);

        $data = $this->getData();

        foreach ($data as $key => $item) {

            if (
                isset($item['id'])
                && (int) $item['id'] === (int) $id
            ) {

                $data[$key]['message'] =
                    $validated['message'];

                $data[$key]['updated_at'] =
                    now()->format('Y-m-d H:i:s');

                $this->saveData($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật trao đổi thành công',
                    'data' => $data[$key]
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy trao đổi'
        ], 404);
    }

    /**
     * DELETE /api/exchanges/{id}
     */
    public function destroy($id)
    {
        $data = $this->getData();

        foreach ($data as $key => $item) {

            if (
                isset($item['id'])
                && (int) $item['id'] === (int) $id
            ) {

                $deleted = $data[$key];

                unset($data[$key]);

                $data = array_values($data);

                $this->saveData($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Xóa trao đổi thành công',
                    'data' => $deleted
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy trao đổi'
        ], 404);
    }

    /**
     * GET /api/requests/{requestId}/exchanges
     *
     * Lấy toàn bộ trao đổi của một yêu cầu.
     *
     * Có thể thêm:
     *
     * ?user_id=101&with_user_id=301
     *
     * để chỉ lấy cuộc trò chuyện
     * giữa user 101 và user 301.
     */
    public function byRequest(Request $request, $requestId)
    {
        $data = $this->getData();

        /*
        |--------------------------------------------------------------------------
        | Lọc theo request
        |--------------------------------------------------------------------------
        */

        $result = array_values(
            array_filter($data, function ($item) use ($requestId) {

                return isset($item['request_id'])
                    && (int) $item['request_id']
                    === (int) $requestId;
            })
        );

        /*
        |--------------------------------------------------------------------------
        | Nếu có user_id + with_user_id
        | thì chỉ lấy cuộc trò chuyện giữa 2 người
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('user_id')
            && $request->filled('with_user_id')
        ) {

            $userId = (int) $request->user_id;

            $withUserId = (int) $request->with_user_id;

            $result = array_values(
                array_filter(
                    $result,
                    function ($item) use ($userId, $withUserId) {

                        $senderId = isset($item['sender_id'])
                            ? (int) $item['sender_id']
                            : null;

                        $receiverId = isset($item['receiver_id'])
                            ? (int) $item['receiver_id']
                            : null;

                        return (
                            $senderId === $userId
                            && $receiverId === $withUserId
                        )
                        ||
                        (
                            $senderId === $withUserId
                            && $receiverId === $userId
                        );
                    }
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sắp xếp tin nhắn cũ -> mới
        |--------------------------------------------------------------------------
        */

        usort($result, function ($a, $b) {

            return strcmp(
                $a['created_at'] ?? '',
                $b['created_at'] ?? ''
            );
        });

        return response()->json([
            'success' => true,

            'message' =>
                'Lấy trao đổi của yêu cầu thành công',

            'request_id' => (int) $requestId,

            'data' => $result
        ]);
    }
}