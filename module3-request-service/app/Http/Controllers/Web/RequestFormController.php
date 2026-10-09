<?php

namespace App\Http\Controllers\Web;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Models\RequestAttachment;
use App\Models\SupportRequest;
use App\Services\ImageStorage;
use App\Services\RequestFormData;

class RequestFormController extends Controller
{
    public function show(int $type)
    {
        return response()->json(['data' => app(RequestFormData::class)->definition($type)]);
    }

    public function download(SupportRequest $supportRequest, string $field, AuthContext $auth)
    {
        $allowed = match ($auth->role()) {
            'admin' => true,
            'department_head' => $supportRequest->department_id === $auth->departmentId(),
            'staff' => $supportRequest->assigned_to === $auth->userId(),
            'student' => $supportRequest->student_id === $auth->userId(),
            default => false,
        };
        abort_unless($allowed, 403);
        $item = collect($supportRequest->form_data ?? [])->firstWhere('key', $field);
        abort_unless($item && ! empty($item['path']), 404);

        return app(ImageStorage::class)->response($item['path'], basename($item['value']), 'local', true);
    }

    public function attachment(SupportRequest $supportRequest, RequestAttachment $attachment, AuthContext $auth)
    {
        abort_unless($attachment->request_id === $supportRequest->id, 404);
        $allowed = match ($auth->role()) {
            'admin' => true,
            'department_head' => $supportRequest->department_id === $auth->departmentId(),
            'staff' => $supportRequest->assigned_to === $auth->userId(),
            'student' => $supportRequest->student_id === $auth->userId(),
            default => false,
        };
        abort_unless($allowed, 403);

        return app(ImageStorage::class)->response($attachment->path, $attachment->original_name, 'public');
    }
}
