<?php

namespace App\Providers;

use App\Contracts\AuthContext;
use App\Services\Auth\JwtAuthContext;
use Illuminate\Support\ServiceProvider;

class AuthContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            JwtAuthContext::class
        );

        $this->app->alias(
            JwtAuthContext::class,
            AuthContext::class
        );
    }
}
