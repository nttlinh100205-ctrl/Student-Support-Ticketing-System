<?php

namespace App\Http\Controllers\Web;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Services\RequestFormData;
use Illuminate\Support\Facades\Storage;

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
        abort_unless($item && ! empty($item['path']) && Storage::disk('local')->exists($item['path']), 404);

        return Storage::disk('local')->download($item['path'], basename($item['value']), ['X-Content-Type-Options' => 'nosniff']);
    }
}
