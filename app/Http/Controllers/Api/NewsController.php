<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | File dữ liệu
    |--------------------------------------------------------------------------
    */

    private function dataFile()
    {
        return storage_path('app/mock_data/news.json');
    }


    /*
    |--------------------------------------------------------------------------
    | Đọc dữ liệu
    |--------------------------------------------------------------------------
    */

    private function readData()
    {
        $file = $this->dataFile();

        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);

        if (!$content) {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }


    /*
    |--------------------------------------------------------------------------
    | Ghi dữ liệu
    |--------------------------------------------------------------------------
    */

    private function writeData(array $data)
    {
        $directory = dirname($this->dataFile());

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $this->dataFile(),
            json_encode(
                $data,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Tên chuyên mục
    |--------------------------------------------------------------------------
    */

    private function categoryName($category)
    {
        $categories = [
            'dao_tao' => 'Phòng Đào tạo',
            'y_te' => 'Phòng Y tế',
            'vat_chat' => 'Phòng Vật chất',
            'ke_toan' => 'Phòng Kế toán',
            'hoat_dong_sinh_vien' => 'Hoạt động sinh viên',
            'nha_truong' => 'Tin nhà trường',
        ];

        return $categories[$category] ?? 'Tin tức khác';
    }


    /*
    |--------------------------------------------------------------------------
    | Xác định quyền
    |--------------------------------------------------------------------------
    */

    private function canManageAll($role)
    {
        return $role === 'admin';
    }


    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH TIN TỨC CHO SINH VIÊN
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $data = $this->readData();

        /*
        | Chỉ hiển thị tin đã đăng
        */

        $data = array_values(
            array_filter($data, function ($item) {
                return ($item['status'] ?? 'published') === 'published';
            })
        );


        /*
        | Tìm kiếm
        */

        if ($request->filled('keyword')) {

            $keyword = mb_strtolower(
                trim($request->keyword)
            );

            $data = array_values(
                array_filter($data, function ($item) use ($keyword) {

                    $title = mb_strtolower(
                        $item['title'] ?? ''
                    );

                    $content = mb_strtolower(
                        $item['content'] ?? ''
                    );

                    $category = mb_strtolower(
                        $item['category_name'] ?? ''
                    );

                    return
                        str_contains($title, $keyword) ||
                        str_contains($content, $keyword) ||
                        str_contains($category, $keyword);
                })
            );
        }


        /*
        | Lọc chuyên mục
        */

        if ($request->filled('category')) {

            $category = $request->category;

            $data = array_values(
                array_filter($data, function ($item) use ($category) {
                    return ($item['category'] ?? '') === $category;
                })
            );
        }


        /*
        | Từ ngày
        */

        if ($request->filled('from_date')) {

            $fromDate = $request->from_date;

            $data = array_values(
                array_filter($data, function ($item) use ($fromDate) {

                    $date = substr(
                        $item['published_at'] ?? '',
                        0,
                        10
                    );

                    return $date >= $fromDate;
                })
            );
        }


        /*
        | Đến ngày
        */

        if ($request->filled('to_date')) {

            $toDate = $request->to_date;

            $data = array_values(
                array_filter($data, function ($item) use ($toDate) {

                    $date = substr(
                        $item['published_at'] ?? '',
                        0,
                        10
                    );

                    return $date <= $toDate;
                })
            );
        }


        /*
        | Tin ghim lên trước
        */

        usort($data, function ($a, $b) {

            $pinnedA = !empty($a['is_pinned']) ? 1 : 0;
            $pinnedB = !empty($b['is_pinned']) ? 1 : 0;

            if ($pinnedA !== $pinnedB) {
                return $pinnedB <=> $pinnedA;
            }

            return strcmp(
                $b['published_at'] ?? '',
                $a['published_at'] ?? ''
            );
        });


        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => null,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN XEM TẤT CẢ
    |--------------------------------------------------------------------------
    */

    public function all(Request $request)
    {
        $data = $this->readData();


        /*
        | Tìm kiếm
        */

        if ($request->filled('keyword')) {

            $keyword = mb_strtolower(
                trim($request->keyword)
            );

            $data = array_values(
                array_filter($data, function ($item) use ($keyword) {

                    return
                        str_contains(
                            mb_strtolower($item['title'] ?? ''),
                            $keyword
                        ) ||
                        str_contains(
                            mb_strtolower($item['content'] ?? ''),
                            $keyword
                        ) ||
                        str_contains(
                            mb_strtolower($item['category_name'] ?? ''),
                            $keyword
                        );
                })
            );
        }


        /*
        | Lọc phòng
        */

        if ($request->filled('category')) {

            $category = $request->category;

            $data = array_values(
                array_filter($data, function ($item) use ($category) {

                    return ($item['category'] ?? '') === $category;
                })
            );
        }


        /*
        | Lọc người đăng
        */

        if ($request->filled('owner_id')) {

            $ownerId = (int) $request->owner_id;

            $data = array_values(
                array_filter($data, function ($item) use ($ownerId) {

                    return (int) ($item['owner_id'] ?? 0) === $ownerId;
                })
            );
        }


        usort($data, function ($a, $b) {

            return strcmp(
                $b['created_at'] ?? '',
                $a['created_at'] ?? ''
            );
        });


        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => null,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | XEM CHI TIẾT
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $data = $this->readData();

        foreach ($data as $item) {

            if ((int) $item['id'] === (int) $id) {

                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'message' => null,
                ]);
            }
        }


        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Không tìm thấy tin tức.',
        ], 404);
    }


    /*
    |--------------------------------------------------------------------------
    | THÊM TIN
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'content' => [
                'required',
                'string',
            ],

            'category' => [
                'required',
                'in:dao_tao,y_te,vat_chat,ke_toan,hoat_dong_sinh_vien,nha_truong',
            ],

            'owner_id' => [
                'required',
                'integer',
            ],

            'owner_name' => [
                'required',
                'string',
                'max:255',
            ],

            'owner_role' => [
                'required',
                'in:admin,department,student',
            ],

            'status' => [
                'nullable',
                'in:draft,published',
            ],

            'is_pinned' => [
                'nullable',
            ],

            'file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],

        ]);


        /*
        | Kiểm tra quyền đăng
        |
        | Admin:
        | - Được đăng mọi chuyên mục
        |
        | Department:
        | - Chỉ được đăng chuyên mục của mình
        |
        | Student:
        | - Chỉ được đăng tin sinh viên
        */

        if (
            $validated['owner_role'] === 'department'
        ) {

            $allowed = [
                'dao_tao',
                'y_te',
                'vat_chat',
                'ke_toan',
            ];

            if (!in_array(
                $validated['category'],
                $allowed
            )) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Phòng chỉ được đăng tin thuộc phòng mình.',
                ], 403);
            }
        }


        if (
            $validated['owner_role'] === 'student'
            &&
            !in_array(
                $validated['category'],
                [
                    'hoat_dong_sinh_vien',
                    'nha_truong',
                ]
            )
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Sinh viên chỉ được đăng tin thuộc nhóm sinh viên.',
            ], 403);
        }


        $data = $this->readData();


        /*
        | ID mới
        */

        $newId = 1;

        if (count($data) > 0) {

            $ids = array_column(
                $data,
                'id'
            );

            $newId = max($ids) + 1;
        }


        /*
        | Upload file
        */

        $filePath = null;
        $fileName = null;
        $fileType = null;


        if ($request->hasFile('file')) {

            $file = $request->file('file');

            $filePath =
                $file->store(
                    'news',
                    'public'
                );

            $fileName =
                $file->getClientOriginalName();

            $fileType =
                $file->getClientMimeType();
        }


        $now =
            now()->format(
                'Y-m-d H:i:s'
            );


        $newItem = [

            'id' => $newId,

            'title' =>
                $validated['title'],

            'content' =>
                $validated['content'],

            'category' =>
                $validated['category'],

            'category_name' =>
                $this->categoryName(
                    $validated['category']
                ),

            'owner_id' =>
                (int) $validated['owner_id'],

            'owner_name' =>
                $validated['owner_name'],

            'owner_role' =>
                $validated['owner_role'],

            'status' =>
                $validated['status'] ?? 'published',

            'is_pinned' =>
                $request->boolean('is_pinned'),

            'file_path' =>
                $filePath,

            'file_name' =>
                $fileName,

            'file_type' =>
                $fileType,

            'created_at' =>
                $now,

            'updated_at' =>
                $now,

            'published_at' =>
                $now,
        ];


        $data[] = $newItem;


        $this->writeData($data);


        return response()->json([
            'success' => true,
            'data' => $newItem,
            'message' =>
                'Thêm tin tức thành công.',
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | SỬA TIN
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $data = $this->readData();

        $index = null;


        foreach ($data as $key => $item) {

            if ((int) $item['id'] === (int) $id) {

                $index = $key;

                break;
            }
        }


        if ($index === null) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Không tìm thấy tin tức.',
            ], 404);
        }


        $current = $data[$index];


        $validated = $request->validate([

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'content' => [
                'required',
                'string',
            ],

            'category' => [
                'required',
                'in:dao_tao,y_te,vat_chat,ke_toan,hoat_dong_sinh_vien,nha_truong',
            ],

            'owner_id' => [
                'required',
                'integer',
            ],

            'owner_role' => [
                'required',
                'in:admin,department,student',
            ],

            'status' => [
                'nullable',
                'in:draft,published',
            ],

            'is_pinned' => [
                'nullable',
            ],

            'file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],

        ]);


        /*
        | Quyền sửa
        */

        $role =
            $validated['owner_role'];

        $ownerId =
            (int) $validated['owner_id'];

        $currentOwnerId =
            (int) ($current['owner_id'] ?? 0);


        /*
        | Admin sửa tất cả
        */

        if ($role === 'admin') {

            // Cho phép
        }


        /*
        | Phòng chỉ sửa tin của mình
        */

        elseif ($role === 'department') {

            if (
                $currentOwnerId !== $ownerId
            ) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Phòng chỉ được sửa tin do phòng mình đăng.',
                ], 403);
            }
        }


        /*
        | Sinh viên chỉ sửa tin của mình
        */

        elseif ($role === 'student') {

            if (
                $currentOwnerId !== $ownerId
            ) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Sinh viên chỉ được sửa tin do mình đăng.',
                ], 403);
            }
        }


        /*
        | File mới
        */

        $filePath =
            $current['file_path'] ?? null;

        $fileName =
            $current['file_name'] ?? null;

        $fileType =
            $current['file_type'] ?? null;


        if ($request->hasFile('file')) {

            /*
            | Xóa file cũ
            */

            if (
                $filePath &&
                Storage::disk('public')
                    ->exists($filePath)
            ) {

                Storage::disk('public')
                    ->delete($filePath);
            }


            $file =
                $request->file('file');


            $filePath =
                $file->store(
                    'news',
                    'public'
                );


            $fileName =
                $file->getClientOriginalName();


            $fileType =
                $file->getClientMimeType();
        }


        $data[$index]['title'] =
            $validated['title'];

        $data[$index]['content'] =
            $validated['content'];

        $data[$index]['category'] =
            $validated['category'];

        $data[$index]['category_name'] =
            $this->categoryName(
                $validated['category']
            );

        $data[$index]['status'] =
            $validated['status'] ??
            ($current['status'] ?? 'published');

        $data[$index]['is_pinned'] =
            $request->boolean('is_pinned');

        $data[$index]['file_path'] =
            $filePath;

        $data[$index]['file_name'] =
            $fileName;

        $data[$index]['file_type'] =
            $fileType;

        $data[$index]['updated_at'] =
            now()->format(
                'Y-m-d H:i:s'
            );


        $this->writeData($data);


        return response()->json([
            'success' => true,
            'data' => $data[$index],
            'message' =>
                'Cập nhật tin tức thành công.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | XÓA TIN
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        $id
    ) {

        $data = $this->readData();

        $index = null;


        foreach ($data as $key => $item) {

            if ((int) $item['id'] === (int) $id) {

                $index = $key;

                break;
            }
        }


        if ($index === null) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Không tìm thấy tin tức.',
            ], 404);
        }


        $current =
            $data[$index];


        $role =
            $request->input(
                'owner_role'
            );


        $ownerId =
            (int) $request->input(
                'owner_id'
            );


        /*
        | Admin được xóa tất cả
        */

        if ($role !== 'admin') {

            if (
                (int) ($current['owner_id'] ?? 0)
                !== $ownerId
            ) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Bạn không có quyền xóa tin này.',
                ], 403);
            }
        }


        /*
        | Xóa file
        */

        if (
            !empty($current['file_path']) &&
            Storage::disk('public')
                ->exists(
                    $current['file_path']
                )
        ) {

            Storage::disk('public')
                ->delete(
                    $current['file_path']
                );
        }


        unset($data[$index]);


        $data =
            array_values($data);


        $this->writeData($data);


        return response()->json([
            'success' => true,
            'data' => null,
            'message' =>
                'Xóa tin tức thành công.',
        ]);
    }
}