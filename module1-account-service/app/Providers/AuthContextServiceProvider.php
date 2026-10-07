<?php

namespace App\Providers;

use App\Contracts\AuthContext;
use App\Services\Auth\SanctumAuthContext;
use Illuminate\Support\ServiceProvider;

class AuthContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AuthContext::class,
            SanctumAuthContext::class
        );
    }
}