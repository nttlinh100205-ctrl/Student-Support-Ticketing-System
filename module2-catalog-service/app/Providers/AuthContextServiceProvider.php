<?php

namespace App\Providers;

use App\Contracts\AuthContext;
use App\Services\Auth\AccountAuthContext;
use App\Services\Auth\FakeHeaderAuthContext;
use Illuminate\Support\ServiceProvider;

class AuthContextServiceProvider extends ServiceProvider
{
    /** Dùng danh tính Module 1; fake auth chỉ bật tường minh trong local/test. */
    public function register(): void
    {
        $this->app->bind(
            AuthContext::class,
            config('account.fake') ? FakeHeaderAuthContext::class : AccountAuthContext::class
        );
    }
}
