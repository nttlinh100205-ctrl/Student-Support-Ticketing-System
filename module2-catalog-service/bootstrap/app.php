<?php

use App\Http\Middleware\AccountAuthentication;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account.auth' => AccountAuthentication::class,
            'auth.fake' => AccountAuthentication::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Lỗi validate (422): giữ danh sách lỗi theo từng field trong "errors".
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Dữ liệu không hợp lệ.', 422, $e->errors());
            }
        });

        // Lỗi HTTP (403, 404, 405...): giữ message tự viết bằng abort(),
        // còn message mặc định tiếng Anh của Laravel thì thay bằng câu tiếng Việt.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $isCustomMessage = $request->route() !== null
                && $e->getPrevious() === null
                && $e->getMessage() !== '';

            $message = $isCustomMessage ? $e->getMessage() : match ($e->getStatusCode()) {
                401 => 'Bạn chưa đăng nhập.',
                403 => 'Bạn không có quyền truy cập chức năng này.',
                404 => 'Không tìm thấy dữ liệu.',
                405 => 'Phương thức HTTP không được hỗ trợ.',
                default => 'Đã có lỗi xảy ra.',
            };

            return ApiResponse::error($message, $e->getStatusCode());
        });
    })
    ->create();
