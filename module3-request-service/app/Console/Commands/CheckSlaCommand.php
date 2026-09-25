<?php

namespace App\Console\Commands;

use App\Services\SlaService;
use Illuminate\Console\Command;

/**
 * Artisan command chạy định kỳ (cron) để kiểm tra SLA.
 *
 * Usage:
 *   php artisan sla:check          — chạy quét bình thường
 *   php artisan sla:check --dry    — chỉ hiển thị kết quả, không cập nhật DB
 *
 * Schedule: Đăng ký trong routes/console.php hoặc App\Console\Kernel
 */
class CheckSlaCommand extends Command
{
    protected $signature = 'sla:check
                            {--dry : Chạy thử, chỉ hiển thị kết quả mà không cập nhật DB}';

    protected $description = 'Kiểm tra SLA tất cả ticket đang mở — đổi cờ cảnh báo và ghi thông báo';

    public function handle(SlaService $slaService): int
    {
        $this->components->info('🔍 Bắt đầu quét SLA...');

        $isDry = $this->option('dry');

        if ($isDry) {
            $this->components->warn('Chế độ DRY RUN — không cập nhật database.');
            // TODO: implement dry-run mode in SlaService if needed
        }

        $start = microtime(true);
        $result = $slaService->checkAll();
        $elapsed = round((microtime(true) - $start) * 1000);

        $this->newLine();
        $this->components->twoColumnDetail('⚠️  Ticket sắp quá hạn (warning)', (string) $result['warned']);
        $this->components->twoColumnDetail('🚨 Ticket đã quá hạn (breached)', (string) $result['breached']);
        $this->components->twoColumnDetail('⏱️  Thời gian xử lý', "{$elapsed}ms");
        $this->newLine();

        if ($result['breached'] > 0) {
            $this->components->error("Có {$result['breached']} ticket đã vi phạm SLA!");
        } elseif ($result['warned'] > 0) {
            $this->components->warn("Có {$result['warned']} ticket sắp quá hạn SLA.");
        } else {
            $this->components->info('✅ Tất cả ticket đang trong hạn SLA.');
        }

        return self::SUCCESS;
    }
}
