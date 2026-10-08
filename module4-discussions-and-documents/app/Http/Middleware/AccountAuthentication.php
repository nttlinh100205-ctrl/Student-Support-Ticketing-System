<?php

namespace App\Http\Middleware;

use App\Services\Auth\AccountClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AccountAuthentication
{
    public function __construct(private AccountClient $accounts) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?: ($request->hasSession() ? $request->session()->get('account_token') : null);
        if (! $token) {
            return $this->unauthenticated($request);
        }
        try {
            $user = $this->accounts->identity($token);
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() === 401) {
                if ($request->hasSession()) {
                    $request->session()->forget('account_token');
                }

                return $this->unauthenticated($request);
            }
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
            }
            throw $e;
        }
        $request->attributes->set('account_user', $user);
        $request->attributes->set('account_token', $token);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function unauthenticated(Request $request)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập.'], 401);
        }
        if ($request->isMethod('GET')) {
            $request->session()->put('account_intended', '/'.ltrim($request->path(), '/'));
        }

        return redirect()->route('account.start');
    }
}
