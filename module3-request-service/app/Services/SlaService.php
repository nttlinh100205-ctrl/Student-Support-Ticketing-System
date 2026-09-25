<?php

namespace App\Services;

use App\Enums\SlaFlag;
use App\Models\SlaNotification;
use App\Models\SupportRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service xử lý logic SLA:
 *   1. Tính deadline khi tạo ticket.
 *   2. Quét hàng loạt ticket → cập nhật cờ warning / breached.
 *   3. Ghi bản ghi thông báo (sla_notifications) để tránh gửi trùng.
 */
class SlaService
{
    /**
     * Tính sla_deadline_at cho ticket mới dựa trên priority.
     * Gọi trong RequestWorkflowService::create().
     */
    public function calculateDeadline(string $priority, Carbon $createdAt): Carbon
    {
        $hours = config("sla.deadline_hours.{$priority}", config('sla.deadline_hours.normal', 24));

        return $createdAt->copy()->addHours($hours);
    }

    /**
     * Quét toàn bộ ticket đang mở → cập nhật sla_flag + tạo thông báo.
     * Được gọi bởi Artisan command `sla:check`.
     *
     * @return array{warned: int, breached: int}
     */
    public function checkAll(): array
    {
        $now = Carbon::now();
        $excludedStatuses = config('sla.excluded_statuses', ['resolved', 'closed', 'cancelled']);
        $warningPercent = config('sla.warning_threshold_percent', 75) / 100;

        $counters = ['warned' => 0, 'breached' => 0];

        // Chỉ quét ticket chưa resolved/closed/cancelled VÀ có sla_deadline_at
        SupportRequest::whereNotIn('status', $excludedStatuses)
            ->whereNotNull('sla_deadline_at')
            ->where('sla_flag', '!=', SlaFlag::Breached->value) // đã breached thì không cần quét lại
            ->chunkById(200, function ($tickets) use ($now, $warningPercent, &$counters) {
                foreach ($tickets as $ticket) {
                    $this->evaluateTicket($ticket, $now, $warningPercent, $counters);
                }
            });

        return $counters;
    }

    /**
     * Đánh giá 1 ticket: cập nhật flag + ghi notification nếu cần.
     */
    protected function evaluateTicket(
        SupportRequest $ticket,
        Carbon $now,
        float $warningPercent,
        array &$counters,
    ): void {
        $deadline = $ticket->sla_deadline_at;

        // --- ĐÃ QUÁ HẠN ---
        if ($now->greaterThanOrEqualTo($deadline)) {
            if ($ticket->sla_flag !== SlaFlag::Breached->value) {
                $ticket->sla_flag = SlaFlag::Breached->value;
                $ticket->save();

                $this->recordNotification($ticket, 'breached');
                $counters['breached']++;

                Log::channel('stack')->warning('[SLA BREACHED] Ticket #' . $ticket->code . ' đã quá hạn SLA.', [
                    'ticket_id' => $ticket->id,
                    'deadline'  => $deadline->toDateTimeString(),
                ]);
            }
            return;
        }

        // --- SẮP QUÁ HẠN (warning) ---
        $created   = $ticket->created_at;
        $totalSecs = $created->diffInSeconds($deadline);
        $elapsed   = $created->diffInSeconds($now);

        if ($totalSecs > 0 && ($elapsed / $totalSecs) >= $warningPercent) {
            if ($ticket->sla_flag !== SlaFlag::Warning->value) {
                $ticket->sla_flag = SlaFlag::Warning->value;
                $ticket->save();

                $this->recordNotification($ticket, 'warning');
                $counters['warned']++;

                Log::channel('stack')->info('[SLA WARNING] Ticket #' . $ticket->code . ' sắp quá hạn SLA.', [
                    'ticket_id'  => $ticket->id,
                    'deadline'   => $deadline->toDateTimeString(),
                    'elapsed_%'  => round(($elapsed / $totalSecs) * 100, 1),
                ]);
            }
        }
    }

    /**
     * Ghi bản ghi thông báo vào DB.
     * Unique (request_id, type) → mỗi loại chỉ ghi 1 lần, tránh spam.
     */
    protected function recordNotification(SupportRequest $ticket, string $type): void
    {
        $messages = [
            'warning'  => "⚠️ Ticket [{$ticket->code}] \"{$ticket->title}\" sắp quá hạn SLA. Hạn: {$ticket->sla_deadline_at->format('d/m/Y H:i')}.",
            'breached' => "🚨 Ticket [{$ticket->code}] \"{$ticket->title}\" ĐÃ QUÁ HẠN SLA! Hạn: {$ticket->sla_deadline_at->format('d/m/Y H:i')}.",
        ];

        SlaNotification::updateOrCreate(
            [
                'request_id' => $ticket->id,
                'type'       => $type,
            ],
            [
                'notified_user_id' => $ticket->assigned_to,
                'message'          => $messages[$type] ?? "SLA {$type} cho ticket {$ticket->code}",
                'sent_at'          => now(),
            ],
        );
    }

    /**
     * Lấy danh sách thông báo SLA cho một user (staff / department_head).
     * Dùng ở API endpoint để hiển thị chuông thông báo.
     */
    public function getNotificationsForUser(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return SlaNotification::where('notified_user_id', $userId)
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Reset flag SLA khi ticket được resolved/closed (optional cleanup).
     */
    public function resetFlag(SupportRequest $ticket): void
    {
        // Không reset — giữ flag để report biết ticket nào bị vi phạm SLA.
        // Nếu muốn reset: $ticket->update(['sla_flag' => SlaFlag::OnTime->value]);
    }
}
