<?php

namespace App\Console\Commands;

use App\Services\CatalogData;
use Database\Seeders\UniSupportDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeedUniSupportDemo extends Command
{
    protected $signature = 'unisupport:demo {--email=student@support.test : Tài khoản sinh viên phát triển}';

    protected $description = 'Tạo dữ liệu minh họa từ tài khoản và danh mục thật; không ghi đè yêu cầu đã có.';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Chỉ được chạy trong môi trường local.');

            return self::FAILURE;
        }
        $url = rtrim(config('account.url'), '/');
        $token = null;
        try {
            $login = Http::withOptions(['force_ip_resolve' => 'v4'])->timeout(15)->acceptJson()->post($url.'/api/v1/auth/login', ['email' => $this->option('email'), 'password' => $this->secret('Mật khẩu tài khoản sinh viên phát triển')])->throw()->json();
            $token = $login['token'];
            $user = $login['user'];
            $user['role'] = strtolower($user['role']);
            $user['full_name'] = $user['name'];
            request()->attributes->set('account_token', $token);
            request()->attributes->set('account_user', $user);
            app(CatalogData::class)->load();
            app(UniSupportDemoSeeder::class)->run();
            $this->info('Đã tạo hồ sơ minh họa. Chạy lại không ghi đè dữ liệu.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Không tạo được dữ liệu. Kiểm tra tài khoản @support.test, mật khẩu và các service 1/2 đang chạy.');

            return self::FAILURE;
        } finally {
            if ($token) {
                try {
                    Http::withOptions(['force_ip_resolve' => 'v4'])->timeout(5)->withToken($token)->post($url.'/api/v1/auth/logout');
                } catch (\Throwable $e) {
                }
            }
        }
    }
}
