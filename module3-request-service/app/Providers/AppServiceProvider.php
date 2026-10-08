<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('workspace-menu', function (?object $authenticated, string $roles) {
            $identity = request()->attributes->get('account_user') ?? (config('account.fake') ? session('fake_user', []) : []);

            return $roles === '' || in_array($identity['role'] ?? '', explode(' ', $roles), true);
        });
    }
}
