<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    private function getData()
    {
        $path = 'mock_data/documents.json';

        if (!Storage::exists($path)) {
            Storage::put($path, json_encode([], JSON_PRETTY_PRINT));
        }

        $data = json_decode(Storage::get($path), true);

        return $data ?? [];
    }

    private function saveData($data)
    {
        Storage::put(
            'mock_data/documents.json',
            json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * GET /api/documents
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
            'message' => 'Lấy danh sách tài liệu thành công',
            'data' => $data
        ]);
    }

    /**
     * GET /api/documents/{id}
     */
    public function show($id)
    {
        $data = $this->getData();

        foreach ($data as $item) {

            if ($item['id'] == $id) {

                return response()->json([
                    'success' => true,
                    'message' => 'Lấy thông tin tài liệu thành công',
                    'data' => $item
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy tài liệu'
        ], 404);
    }

    /**
     * POST /api/documents
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file_url' => 'required|string',
            'file_type' => 'required|string|max:50',
            'file_size' => 'nullable|string',
            'uploaded_by' => 'required|integer',
            'uploaded_by_name' => 'required|string|max:255'
        ]);

        $data = $this->getData();

        $newId = empty($data)
            ? 1
            : max(array_column($data, 'id')) + 1;

        $newDocument = [
            'id' => $newId,
            'request_id' => $validated['request_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'file_url' => $validated['file_url'],
            'file_type' => $validated['file_type'],
            'file_size' => $validated['file_size'] ?? '',
            'uploaded_by' => $validated['uploaded_by'],
            'uploaded_by_name' => $validated['uploaded_by_name'],
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s')
        ];

        $data[] = $newDocument;

        $this->saveData($data);

        return response()->json([
            'success' => true,
            'message' => 'Thêm tài liệu thành công',
            'data' => $newDocument
        ], 201);
    }

    /**
     * PUT /api/documents/{id}
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file_url' => 'required|string'
        ]);

        $data = $this->getData();

        foreach ($data as $key => $item) {

            if ($item['id'] == $id) {

                $data[$key]['name'] = $validated['name'];
                $data[$key]['description'] =
                    $validated['description'] ?? '';
                $data[$key]['file_url'] =
                    $validated['file_url'];

                $data[$key]['updated_at'] =
                    now()->format('Y-m-d H:i:s');

                $this->saveData($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật tài liệu thành công',
                    'data' => $data[$key]
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy tài liệu'
        ], 404);
    }

    /**
     * DELETE /api/documents/{id}
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
                    'message' => 'Xóa tài liệu thành công',
                    'data' => $deleted
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy tài liệu'
        ], 404);
    }

    /**
     * GET /api/requests/{requestId}/documents
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
            'message' => 'Lấy tài liệu của yêu cầu thành công',
            'request_id' => (int) $requestId,
            'data' => $result
        ]);
    }
}