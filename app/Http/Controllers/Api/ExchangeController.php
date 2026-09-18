<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExchangeController extends Controller
{
    private function getData()
    {
        $path = 'mock_data/exchanges.json';

        if (!Storage::exists($path)) {
            Storage::put($path, json_encode([], JSON_PRETTY_PRINT));
        }

        $data = json_decode(Storage::get($path), true);

        return $data ?? [];
    }

    private function saveData($data)
    {
        Storage::put(
            'mock_data/exchanges.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * GET /api/exchanges
     */
    public function index(Request $request)
    {
        $data = $this->getData();

        if ($request->has('request_id')) {
            $data = array_values(
                array_filter($data, function ($item) use ($request) {
                    return $item['request_id'] == $request->request_id;
                })
            );
        }

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
            if ($item['id'] == $id) {
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
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|integer',
            'sender_id' => 'required|integer',
            'sender_name' => 'required|string|max:255',
            'sender_role' => 'required|in:student,staff',
            'message' => 'required|string'
        ]);

        $data = $this->getData();

        $newId = empty($data)
            ? 1
            : max(array_column($data, 'id')) + 1;

        $newExchange = [
            'id' => $newId,
            'request_id' => $validated['request_id'],
            'sender_id' => $validated['sender_id'],
            'sender_name' => $validated['sender_name'],
            'sender_role' => $validated['sender_role'],
            'message' => $validated['message'],
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s')
        ];

        $data[] = $newExchange;

        $this->saveData($data);

        return response()->json([
            'success' => true,
            'message' => 'Thêm trao đổi thành công',
            'data' => $newExchange
        ], 201);
    }

    /**
     * PUT /api/exchanges/{id}
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'message' => 'required|string'
        ]);

        $data = $this->getData();

        foreach ($data as $key => $item) {

            if ($item['id'] == $id) {

                $data[$key]['message'] = $validated['message'];

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

            if ($item['id'] == $id) {

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
     */
    public function byRequest($requestId)
    {
        $data = $this->getData();

        $result = array_values(
            array_filter($data, function ($item) use ($requestId) {
                return $item['request_id'] == $requestId;
            })
        );

        return response()->json([
            'success' => true,
            'message' => 'Lấy trao đổi của yêu cầu thành công',
            'request_id' => (int) $requestId,
            'data' => $result
        ]);
    }
}