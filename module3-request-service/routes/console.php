<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| SLA Monitor — Cron Job
|--------------------------------------------------------------------------
| Chạy lệnh sla:check mỗi N phút (mặc định 5p, cấu hình trong config/sla.php).
| withoutOverlapping() → nếu lần trước chưa chạy xong thì bỏ qua.
| appendOutputTo() → ghi log output để debug khi cần.
|
| Để kích hoạt cron trên server:
|   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|
| Trên Windows (Task Scheduler) hoặc dev:
|   php artisan schedule:work
|--------------------------------------------------------------------------
*/
$intervalMinutes = (int) config('sla.check_interval_minutes', 5);

Schedule::command('sla:check')
    ->everyFiveMinutes()
    ->when(fn () => $intervalMinutes <= 5)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sla-check.log'))
    ->onOneServer()
    ->description('Kiểm tra SLA ticket — quét warning/breached');

// Nếu cấu hình > 5 phút, dùng cron expression thay thế
if ($intervalMinutes > 5) {
    Schedule::command('sla:check')
        ->cron("*/{$intervalMinutes} * * * *")
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/sla-check.log'))
        ->onOneServer()
        ->description('Kiểm tra SLA ticket — quét warning/breached');
}

