<?php

namespace App\Providers;

use App\Contracts\AuthContext;
use App\Services\Auth\FakeHeaderAuthContext;
use Illuminate\Support\ServiceProvider;

class AuthContextServiceProvider extends ServiceProvider
{
    /**
     * Khi Module 1 (Auth) làm JWT thật xong:
     * đổi FakeHeaderAuthContext::class -> JwtAuthContext::class.
     */
    public function register(): void
    {
        $this->app->bind(
            AuthContext::class,
            FakeHeaderAuthContext::class
        );
    }
}
