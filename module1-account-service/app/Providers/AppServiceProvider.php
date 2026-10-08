<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(
            function (
                User $user,
                string $token
            ): string {
                $frontendUrl = rtrim(
                    (string) config(
                        'app.frontend_url'
                    ),
                    '/'
                );

                return $frontendUrl
                    .'/reset-password/'
                    .urlencode($token)
                    .'?email='
                    .urlencode($user->email);
            }
        );
    }
}