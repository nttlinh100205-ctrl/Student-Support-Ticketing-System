<?php

namespace App\Http\Controllers;

use App\Services\Auth\AccountClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AccountSessionController extends Controller
{
    public function start(Request $request)
    {
        $state = Str::random(64);
        $verifier = Str::random(64);
        $request->session()->put('account_login', ['state' => $state, 'verifier' => $verifier]);

        return redirect()->away(rtrim(config('account.url'), '/').'/sso/authorize?'.http_build_query([
            'service' => config('account.service'), 'state' => $state,
            'challenge' => hash('sha256', $verifier),
        ]));
    }

    public function callback(Request $request, AccountClient $accounts)
    {
        $data = $request->validate(['state' => 'required|string|size:64', 'code' => 'required|string|size:64']);
        $login = $request->session()->pull('account_login');
        abort_unless(is_array($login) && hash_equals($login['state'], $data['state']), 419, 'Phiên đăng nhập đã hết hạn.');
        $result = $accounts->send('POST', '/api/v1/auth/sso/exchange', null, [
            'code' => $data['code'], 'verifier' => $login['verifier'], 'service' => config('account.service'),
        ]);
        abort_unless(is_string($result['token'] ?? null), 503);
        $accounts->identity($result['token']);
        $request->session()->regenerate();
        $request->session()->put('account_token', $result['token']);
        $target = $request->session()->pull('account_intended', '/');

        return redirect($target)->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }

    public function logout(Request $request, AccountClient $accounts)
    {
        $token = $request->session()->get('account_token');
        if ($token) {
            try {
                $accounts->send('POST', '/api/v1/auth/logout', $token);
            } catch (HttpExceptionInterface $e) {
                if ($e->getStatusCode() !== 401) {
                    throw $e;
                }
            }
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(rtrim(config('account.url'), '/').'/login?logged_out=1');
    }
}
