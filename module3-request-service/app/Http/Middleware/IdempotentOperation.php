<?php

namespace App\Http\Middleware;

use App\Contracts\AuthContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IdempotentOperation
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key') ?? $request->input('_idempotency_key');
        // API cũ vẫn hoạt động; giao diện luôn gửi khóa cho mỗi lần soạn mới.
        if ($key === null) {
            return $next($request);
        }
        abort_unless(is_string($key) && preg_match('/^[a-zA-Z0-9_-]{16,128}$/D', $key), 422, 'Khóa gửi yêu cầu không hợp lệ.');
        $identity = ['user_id' => app(AuthContext::class)->userId(), 'scope' => $request->path(), 'operation_key' => $key];
        $fingerprint = hash('sha256', json_encode($this->normalize($request->except(['_token', '_idempotency_key'])), JSON_THROW_ON_ERROR));

        // Khóa unique và transaction chung bảo đảm chỉ một tiến trình thực hiện nghiệp vụ.
        return DB::transaction(function () use ($request, $next, $identity, $fingerprint) {
            DB::table('idempotent_operations')->insertOrIgnore([...$identity, 'fingerprint' => $fingerprint, 'created_at' => now(), 'updated_at' => now()]);
            $operation = DB::table('idempotent_operations')->where($identity)->lockForUpdate()->first();
            abort_unless($operation && hash_equals($operation->fingerprint, $fingerprint), 409, 'Khóa này đã được dùng cho nội dung khác. Hãy bắt đầu lần gửi mới.');
            if ($operation->status !== null) {
                $response = response($operation->body ?? '', $operation->status);
                if ($operation->location) {
                    $response->headers->set('Location', $operation->location);
                    if ($request->hasSession()) {
                        $request->session()->flash('success', 'Thao tác này đã được ghi nhận trước đó.');
                    }
                }
                if ($operation->content_type) {
                    $response->headers->set('Content-Type', $operation->content_type);
                }

                return $response->header('Idempotency-Replayed', 'true');
            }
            $response = $next($request);
            if (! $request->attributes->get('operation_completed')) {
                DB::table('idempotent_operations')->where('id', $operation->id)->delete();

                return $response;
            }
            DB::table('idempotent_operations')->where('id', $operation->id)->update([
                'status' => $response->getStatusCode(), 'body' => $response->getContent(),
                'location' => $response->headers->get('Location'), 'content_type' => $response->headers->get('Content-Type'), 'updated_at' => now(),
            ]);

            return $response;
        });
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return ['filename' => $value->getClientOriginalName(), 'sha256' => hash_file('sha256', $value->getRealPath())];
        }
        if (is_array($value)) {
            ksort($value);

            return array_map(fn ($item) => $this->normalize($item), $value);
        }

        return $value;
    }
}
