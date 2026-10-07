<?php

namespace App\Providers;

use App\Contracts\AuthContextInterface;
use App\Contracts\OrgServiceClientInterface;
use App\Contracts\RequestServiceClientInterface;
use App\Services\Auth\FakeHeaderAuthContext;
use App\Services\Clients\OrgServiceClient;
use App\Services\Clients\RequestServiceClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthContextInterface::class, FakeHeaderAuthContext::class);
        $this->app->bind(RequestServiceClientInterface::class, RequestServiceClient::class);
        $this->app->bind(OrgServiceClientInterface::class, OrgServiceClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
