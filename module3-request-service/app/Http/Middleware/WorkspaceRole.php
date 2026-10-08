<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WorkspaceRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->attributes->get('account_user') ?? (config('account.fake') ? session('fake_user') : null);
        abort_unless($user && in_array($user['role'], $roles, true), 403, 'Bạn không có quyền truy cập chức năng này.');

        return $next($request);
    }
}
