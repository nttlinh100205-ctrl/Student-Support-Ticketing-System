<?php

namespace App\Providers;

use App\Contracts\AuthContext;
use App\Services\Auth\FakeHeaderAuthContext;
use Illuminate\Support\ServiceProvider;

class AuthContextServiceProvider extends ServiceProvider
{
    /**
     * Nơi DUY NHẤT cần sửa khi Module 1 (Auth) làm JWT thật xong:
     * đổi FakeHeaderAuthContext::class -> JwtAuthContext::class.
     * Toàn bộ Controller/Service dùng AuthContext (type-hint interface)
     * sẽ tự động dùng implementation mới, không cần sửa code nghiệp vụ.
     */
    public function register(): void
    {
        $this->app->bind(AuthContext::class, FakeHeaderAuthContext::class);
    }
}
