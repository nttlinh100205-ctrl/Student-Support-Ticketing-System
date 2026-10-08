<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SingleSignOnController extends Controller
{
    public function ticket(Request $request)
    {
        $data = $request->validate([
            'service' => ['required', Rule::in(array_keys(config('portal.services')))],
            'state' => ['required', 'string', 'size:64', 'alpha_num'],
            'challenge' => ['required', 'regex:/^[a-f0-9]{64}$/'],
        ]);
        abort_unless($request->user()->status === 'ACTIVE', 403);
        abort_unless($request->bearerToken(), 401);
        $code = Str::random(64);
        Cache::put('sso:'.hash('sha256', $code), [
            'service' => $data['service'], 'challenge' => $data['challenge'],
            'token' => Crypt::encryptString($request->bearerToken()),
        ], now()->addMinute());

        return response()->json(['redirect' => rtrim(config('portal.services.'.$data['service']), '/').'/auth/callback?'.http_build_query([
            'code' => $code, 'state' => $data['state'],
        ])])->header('Cache-Control', 'no-store');
    }

    public function exchange(Request $request)
    {
        $data = $request->validate([
            'service' => ['required', Rule::in(array_keys(config('portal.services')))],
            'code' => ['required', 'string', 'size:64', 'alpha_num'],
            'verifier' => ['required', 'string', 'size:64', 'alpha_num'],
        ]);
        $key = 'sso:'.hash('sha256', $data['code']);
        $token = Cache::lock($key.':lock', 5)->block(2, function () use ($key, $data) {
            $ticket = Cache::get($key);
            abort_unless(is_array($ticket) && $ticket['service'] === $data['service']
                && hash_equals($ticket['challenge'], hash('sha256', $data['verifier'])), 401);
            Cache::forget($key);

            return Crypt::decryptString($ticket['token']);
        });

        return response()->json(['token' => $token])->header('Cache-Control', 'no-store');
    }
}
