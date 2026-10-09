<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class NewsController extends Controller
{
    private string $jsonFile = 'mock_data/news.json';

    private string $uploadDirectory = 'news';

    public function index(Request $request)
    {
        try {
            $news = $this->readNews();

            $keyword = trim((string) $request->query('keyword', ''));
            $category = trim((string) $request->query('category', ''));

            if ($keyword !== '') {

                $keywordLower = Str::lower($keyword);

                $news = array_filter($news, function ($item) use ($keywordLower) {

                    $title = Str::lower(
                        (string) ($item['title'] ?? '')
                    );

                    $content = Str::lower(
                        (string) ($item['content'] ?? '')
                    );

                    $ownerName = Str::lower(
                        (string) ($item['owner_name'] ?? '')
                    );

                    return
                        Str::contains($title, $keywordLower) ||
                        Str::contains($content, $keywordLower) ||
                        Str::contains($ownerName, $keywordLower);
                });
            }

            if ($category !== '') {

                $news = array_filter($news, function ($item) use ($category) {

                    return Str::lower(
                        (string) ($item['category'] ?? '')
                    ) === Str::lower($category);
                });
            }

            $news = array_values($news);

            usort($news, function ($a, $b) {

                $pinnedA = (int) ($a['is_pinned'] ?? 0);
                $pinnedB = (int) ($b['is_pinned'] ?? 0);

                if ($pinnedA !== $pinnedB) {
                    return $pinnedB <=> $pinnedA;
                }

                $dateA = strtotime(
                    (string) (
                        $a['published_at'] ??
                        $a['created_at'] ??
                        ''
                    )
                );

                $dateB = strtotime(
                    (string) (
                        $b['published_at'] ??
                        $b['created_at'] ??
                        ''
                    )
                );

                return $dateB <=> $dateA;
            });

            $news = array_map(function ($item) {

                return $this->appendFileUrls($item);

            }, $news);

            return response()->json([
                'success' => true,
                'data' => array_values($news),
                'message' => null,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Không thể tải danh sách tin tức: '.
                    $e->getMessage(),
                'errors' => [],
            ], 500);
        }
    }

    public function adminIndex(Request $request)
    {
        abort_unless($request->attributes->get('account_user')['role'] === 'admin', 403);

        return $this->index($request);
    }

    public function store(Request $request)
    {
        $this->setAuthenticatedOwner($request);
        $validator = Validator::make(
            $request->all(),
            [
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
                    'string',
                    'in:dao_tao,y_te,vat_chat,ke_toan,hoat_dong_sinh_vien,nha_truong',
                ],

                'owner_id' => [
                    'required',
                ],

                'owner_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'owner_role' => [
                    'required',
                    'string',
                    'in:admin,department,student',
                ],

                'status' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'is_pinned' => [
                    'nullable',
                    'boolean',
                ],

                'file' => [
                    'nullable',
                    'file',
                    'max:10240',
                    'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
                ],
            ],
            [
                'title.required' => 'Vui lòng nhập tiêu đề.',

                'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',

                'content.required' => 'Vui lòng nhập nội dung.',

                'category.required' => 'Vui lòng chọn chuyên mục.',

                'category.in' => 'Chuyên mục không hợp lệ.',

                'owner_id.required' => 'Thiếu thông tin người đăng.',

                'owner_name.required' => 'Thiếu tên người đăng.',

                'owner_role.required' => 'Thiếu vai trò người đăng.',

                'owner_role.in' => 'Vai trò người đăng không hợp lệ.',

                'file.file' => 'File tải lên không hợp lệ.',

                'file.max' => 'File không được vượt quá 10MB.',

                'file.mimes' => 'File chỉ được phép là JPG, JPEG, PNG, WEBP, PDF, DOC, DOCX, XLS hoặc XLSX.',
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            $news = $this->readNews();

            $id = $this->getNextId($news);

            $now = now()->format(
                'Y-m-d H:i:s'
            );

            $fileData = [];

            if ($request->hasFile('file')) {

                $fileData =
                    $this->saveUploadedFile(
                        $request->file('file')
                    );
            }

            $category =
                trim(
                    (string) $request->input('category')
                );

            $item = [

                'id' => $id,

                'title' => trim(
                    (string) $request->input('title')
                ),

                'content' => trim(
                    (string) $request->input('content')
                ),

                'category' => $category,

                'category_name' => $this->getCategoryName(
                    $category
                ),

                'owner_id' => (int) $request->input('owner_id'),

                'owner_name' => trim(
                    (string) $request->input('owner_name')
                ),

                'owner_role' => trim(
                    (string) $request->input('owner_role')
                ),

                'status' => $request->input(
                    'status',
                    'published'
                ),

                'is_pinned' => $request->boolean(
                    'is_pinned'
                ),

                'published_at' => $now,

                'created_at' => $now,

                'updated_at' => $now,
            ];

            if (! empty($fileData)) {

                $item = array_merge(
                    $item,
                    $fileData
                );
            }

            $news[] = $item;

            $this->writeNews($news);

            return response()->json([
                'success' => true,
                'data' => $this->appendFileUrls(
                    $item
                ),
                'message' => 'Đăng tin thành công.',
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Không thể đăng tin: '.
                    $e->getMessage(),
                'errors' => [],
            ], 500);
        }
    }

    public function update(
        Request $request,
        $id
    ) {
        $this->setAuthenticatedOwner($request);
        $validator = Validator::make(
            $request->all(),
            [
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
                    'string',
                    'in:dao_tao,y_te,vat_chat,ke_toan,hoat_dong_sinh_vien,nha_truong',
                ],

                'owner_id' => [
                    'required',
                ],

                'owner_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'owner_role' => [
                    'nullable',
                    'string',
                    'in:admin,department,student',
                ],

                'status' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'is_pinned' => [
                    'nullable',
                    'boolean',
                ],

                'file' => [
                    'nullable',
                    'file',
                    'max:10240',
                    'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
                ],
            ],
            [
                'title.required' => 'Vui lòng nhập tiêu đề.',

                'content.required' => 'Vui lòng nhập nội dung.',

                'category.required' => 'Vui lòng chọn chuyên mục.',

                'category.in' => 'Chuyên mục không hợp lệ.',

                'file.max' => 'File không được vượt quá 10MB.',

                'file.mimes' => 'File chỉ được phép là JPG, JPEG, PNG, WEBP, PDF, DOC, DOCX, XLS hoặc XLSX.',
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            $news = $this->readNews();

            $index =
                $this->findNewsIndex(
                    $news,
                    $id
                );

            if ($index === null) {

                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tin tức.',
                    'errors' => [],
                ], 404);
            }

            $oldItem = $news[$index];

            $requestOwnerId =
                (string) $request->input(
                    'owner_id'
                );

            $oldOwnerId =
                (string) (
                    $oldItem['owner_id'] ?? ''
                );

            $requestOwnerRole =
                strtolower(
                    (string) $request->input(
                        'owner_role',
                        ''
                    )
                );

            if (
                $requestOwnerRole !== 'admin' &&
                $requestOwnerId !== $oldOwnerId
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền sửa tin này.',
                    'errors' => [],
                ], 403);
            }

            $category =
                trim(
                    (string) $request->input(
                        'category'
                    )
                );

            $item = $oldItem;

            $item['title'] =
                trim(
                    (string) $request->input(
                        'title'
                    )
                );

            $item['content'] =
                trim(
                    (string) $request->input(
                        'content'
                    )
                );

            $item['category'] =
                $category;

            $item['category_name'] =
                $this->getCategoryName(
                    $category
                );

            if ($request->filled('owner_name')) {

                $item['owner_name'] =
                    trim(
                        (string) $request->input(
                            'owner_name'
                        )
                    );
            }

            if ($request->filled('owner_role')) {

                $item['owner_role'] =
                    trim(
                        (string) $request->input(
                            'owner_role'
                        )
                    );
            }

            if ($request->has('status')) {

                $item['status'] =
                    $request->input('status');
            }

            $item['is_pinned'] =
                $request->boolean(
                    'is_pinned'
                );

            $item['updated_at'] =
                now()->format(
                    'Y-m-d H:i:s'
                );

            if ($request->hasFile('file')) {

                $fileData =
                    $this->saveUploadedFile(
                        $request->file('file')
                    );

                $item =
                    array_merge(
                        $item,
                        $fileData
                    );
            }

            $news[$index] = $item;

            $this->writeNews($news);

            if ($request->hasFile('file')) {
                $this->deleteFileFromItem($oldItem);
            }

            return response()->json([
                'success' => true,
                'data' => $this->appendFileUrls(
                    $item
                ),
                'message' => 'Cập nhật tin thành công.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật tin: '.
                    $e->getMessage(),
                'errors' => [],
            ], 500);
        }
    }

    public function destroy(
        Request $request,
        $id
    ) {
        $this->setAuthenticatedOwner($request);
        try {

            $news = $this->readNews();

            $index =
                $this->findNewsIndex(
                    $news,
                    $id
                );

            if ($index === null) {

                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tin tức.',
                    'errors' => [],
                ], 404);
            }

            $item = $news[$index];

            $requestOwnerId =
                (string) $request->input(
                    'owner_id'
                );

            $oldOwnerId =
                (string) (
                    $item['owner_id'] ?? ''
                );

            $requestOwnerRole =
                strtolower(
                    (string) $request->input(
                        'owner_role',
                        ''
                    )
                );

            if (
                $requestOwnerRole !== 'admin' &&
                $requestOwnerId !== $oldOwnerId
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa tin này.',
                    'errors' => [],
                ], 403);
            }

            $this->deleteFileFromItem(
                $item
            );

            array_splice(
                $news,
                $index,
                1
            );

            $this->writeNews($news);

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Xóa tin thành công.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa tin: '.
                    $e->getMessage(),
                'errors' => [],
            ], 500);
        }
    }

    public function viewFile($id)
    {
        try {

            $news = $this->readNews();

            $index =
                $this->findNewsIndex(
                    $news,
                    $id
                );

            if ($index === null) {

                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tin tức.',
                ], 404);
            }

            $item = $news[$index];
            if (app(\App\Services\ImageStorage::class)->isCloud($item['file_path'] ?? '')) {
                return app(\App\Services\ImageStorage::class)->response($item['file_path'], $item['file_name'] ?? 'image', 'public', false);
            }

            $filePath =
                $this->getAbsoluteFilePath(
                    $item
                );

            if (
                ! $filePath ||
                ! is_file($filePath)
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'File không tồn tại trên máy chủ.',
                ], 404);
            }

            $mimeType =
                $this->getMimeType(
                    $item,
                    $filePath
                );

            return response()->file(
                $filePath,
                [
                    'Content-Type' => $mimeType,

                    'Content-Disposition' => 'inline; filename="'.
                        $this->safeHeaderFileName(
                            $item['file_name'] ??
                            basename($filePath)
                        ).
                        '"',

                    'X-Content-Type-Options' => 'nosniff',
                ]
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Không thể xem file: '.
                    $e->getMessage(),
            ], 500);
        }
    }

    public function downloadFile($id)
    {
        try {

            $news = $this->readNews();

            $index =
                $this->findNewsIndex(
                    $news,
                    $id
                );

            if ($index === null) {

                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tin tức.',
                ], 404);
            }

            $item = $news[$index];
            if (app(\App\Services\ImageStorage::class)->isCloud($item['file_path'] ?? '')) {
                return app(\App\Services\ImageStorage::class)->response($item['file_path'], $item['file_name'] ?? 'image', 'public', true);
            }

            $filePath =
                $this->getAbsoluteFilePath(
                    $item
                );

            if (
                ! $filePath ||
                ! is_file($filePath)
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'File không tồn tại trên máy chủ.',
                ], 404);
            }

            $fileName =
                $item['file_name'] ??
                basename($filePath);

            return response()->download(
                $filePath,
                $fileName
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Không thể tải file: '.
                    $e->getMessage(),
            ], 500);
        }
    }

    private function readNews(): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($this->jsonFile)) {

            $disk->makeDirectory(
                dirname($this->jsonFile)
            );

            $disk->put(
                $this->jsonFile,
                json_encode(
                    [],
                    JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_UNICODE
                )
            );

            return [];
        }

        $content =
            $disk->get(
                $this->jsonFile
            );

        if (trim($content) === '') {
            return [];
        }

        $data =
            json_decode(
                $content,
                true
            );

        if (! is_array($data)) {
            return [];
        }

        return array_values($data);
    }

    private function writeNews(
        array $news
    ): void {
        Storage::disk('local')->makeDirectory(
            dirname($this->jsonFile)
        );

        Storage::disk('local')->put(
            $this->jsonFile,
            json_encode(
                array_values($news),
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            )
        );
    }

    private function getNextId(
        array $news
    ): int {
        $maxId = 0;

        foreach ($news as $item) {

            $id =
                (int) ($item['id'] ?? 0);

            if ($id > $maxId) {
                $maxId = $id;
            }
        }

        return $maxId + 1;
    }

    private function findNewsIndex(
        array $news,
        $id
    ): ?int {
        foreach ($news as $index => $item) {

            if (
                (string) ($item['id'] ?? '') ===
                (string) $id
            ) {
                return $index;
            }
        }

        return null;
    }

    private function saveUploadedFile(
        $file
    ): array {
        if (str_starts_with((string) $file->getMimeType(), 'image/')) {
            return ['file_path' => app(\App\Services\ImageStorage::class)->store($file, 'news', 'public'), 'file_name' => $file->getClientOriginalName(), 'file_type' => $file->getMimeType(), 'file_size' => $file->getSize()];
        }
        $disk =
            Storage::disk('public');

        if (
            ! $disk->exists(
                $this->uploadDirectory
            )
        ) {

            $disk->makeDirectory(
                $this->uploadDirectory
            );
        }

        $originalName =
            $file->getClientOriginalName();

        $extension =
            strtolower(
                $file->getClientOriginalExtension()
            );

        $baseName =
            pathinfo(
                $originalName,
                PATHINFO_FILENAME
            );

        $safeBaseName =
            Str::slug($baseName);

        if ($safeBaseName === '') {
            $safeBaseName = 'file';
        }

        $uniqueName =
            $safeBaseName.
            '_'.
            Str::random(20).
            '.'.
            $extension;

        $path =
            $file->storeAs(
                $this->uploadDirectory,
                $uniqueName,
                'public'
            );

        return [
            'file_path' => $path,

            'file_name' => $originalName,

            'file_type' => $this->getUploadedMimeType(
                $file,
                $extension
            ),

            'file_size' => $file->getSize(),
        ];
    }

    private function getUploadedMimeType(
        $file,
        string $extension
    ): string {
        $mime =
            strtolower(
                (string) $file->getClientMimeType()
            );

        if (
            $mime !== '' &&
            $mime !== 'application/octet-stream'
        ) {

            return $mime;
        }

        $mimeMap = [

            'jpg' => 'image/jpeg',

            'jpeg' => 'image/jpeg',

            'png' => 'image/png',

            'webp' => 'image/webp',

            'pdf' => 'application/pdf',

            'doc' => 'application/msword',

            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

            'xls' => 'application/vnd.ms-excel',

            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $mimeMap[$extension]
            ?? 'application/octet-stream';
    }

    private function getMimeType(
        array $item,
        string $filePath
    ): string {
        $storedMime =
            trim(
                (string) (
                    $item['file_type'] ?? ''
                )
            );

        if (
            $storedMime !== '' &&
            $storedMime !== 'application/octet-stream'
        ) {

            return $storedMime;
        }

        $extension =
            strtolower(
                pathinfo(
                    $filePath,
                    PATHINFO_EXTENSION
                )
            );

        $mimeMap = [

            'jpg' => 'image/jpeg',

            'jpeg' => 'image/jpeg',

            'png' => 'image/png',

            'webp' => 'image/webp',

            'pdf' => 'application/pdf',

            'doc' => 'application/msword',

            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

            'xls' => 'application/vnd.ms-excel',

            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        if (
            isset($mimeMap[$extension])
        ) {

            return $mimeMap[$extension];
        }

        $detected =
            @mime_content_type(
                $filePath
            );

        return $detected
            ?: 'application/octet-stream';
    }

    private function getAbsoluteFilePath(
        array $item
    ): ?string {
        $filePath =
            trim(
                (string) (
                    $item['file_path'] ?? ''
                )
            );

        if ($filePath === '') {
            return null;
        }

        $disk =
            Storage::disk('public');

        if (
            ! $disk->exists($filePath)
        ) {
            return null;
        }

        return $disk->path(
            $filePath
        );
    }

    private function deleteFileFromItem(
        array $item
    ): void {
        $filePath =
            trim(
                (string) (
                    $item['file_path'] ?? ''
                )
            );

        if ($filePath === '') {
            return;
        }

        if (app(\App\Services\ImageStorage::class)->isCloud($filePath)) {
            app(\App\Services\ImageStorage::class)->delete($filePath, 'public');
            return;
        }
        $disk =
            Storage::disk('public');

        if (
            $disk->exists($filePath)
        ) {

            $disk->delete(
                $filePath
            );
        }
    }

    private function appendFileUrls(
        array $item
    ): array {
        if (
            ! empty($item['file_path'])
        ) {

            $item['file_url'] =
                url(
                    '/api/news/'.
                    $item['id'].
                    '/file'
                );

            $item['download_url'] =
                url(
                    '/api/news/'.
                    $item['id'].
                    '/download'
                );

        } else {

            $item['file_url'] = null;

            $item['download_url'] = null;
        }

        return $item;
    }

    private function getCategoryName(
        string $category
    ): string {
        $names = [

            'dao_tao' => 'Phòng Đào tạo',

            'y_te' => 'Phòng Y tế',

            'vat_chat' => 'Phòng Vật chất',

            'ke_toan' => 'Phòng Kế toán',

            'hoat_dong_sinh_vien' => 'Hoạt động sinh viên',

            'nha_truong' => 'Tin nhà trường',
        ];

        return $names[$category]
            ?? $category;
    }

    private function safeHeaderFileName(
        string $fileName
    ): string {
        return str_replace(
            [
                '"',
                "\r",
                "\n",
            ],
            '',
            $fileName
        );
    }

    private function setAuthenticatedOwner(Request $request): void
    {
        $user = $request->attributes->get('account_user');
        abort_unless(is_array($user), 401);
        $request->merge([
            'owner_id' => $user['id'], 'owner_name' => $user['full_name'],
            'owner_role' => in_array($user['role'], ['staff', 'department_head'], true) ? 'department' : $user['role'],
        ]);
        if ($user['role'] !== 'admin') {
            $request->merge(['is_pinned' => false]);
        }
    }
}
