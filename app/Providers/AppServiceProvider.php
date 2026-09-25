<?php

namespace App\Providers;
use App\Contracts\AuthContextInterface;
use App\Services\Auth\FakeHeaderAuthContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthContextInterface::class, FakeHeaderAuthContext::class);
    // Sau này: đổi FakeHeaderAuthContext::class -> JwtAuthContext::class là xong,
    // Controller/Service không cần sửa 1 dòng nào.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
