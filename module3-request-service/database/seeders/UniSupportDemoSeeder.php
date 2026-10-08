<?php

namespace Database\Seeders;

use App\Models\RequestStatusHistory;
use App\Models\SupportRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniSupportDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Chỉ tạo dữ liệu minh họa trong môi trường local/testing.');
        }
        $user = request()->attributes->get('account_user');
        if (! $user || $user['role'] !== 'student' || ! str_ends_with($user['email'] ?? '', '@support.test')) {
            throw new \RuntimeException('Chạy php artisan unisupport:demo bằng tài khoản sinh viên phát triển @support.test.');
        }
        $types = config('master_data.support_types', []);
        if (! $types) {
            throw new \RuntimeException('Chưa có loại hỗ trợ. Hãy khởi chạy module danh mục.');
        }
        DB::transaction(function () use ($user, $types) {
            $examples = [
                ['new', 'Xin xác nhận sinh viên cho học kỳ mới'],
                ['in_progress', 'Hướng dẫn hồ sơ hỗ trợ học phí'],
                ['waiting_info', 'Bổ sung giấy tờ xét học bổng'],
                ['resolved', 'Tra cứu lịch tiếp nhận hồ sơ sinh viên'],
                ['closed', 'Hỗ trợ thủ tục đăng ký hoạt động sinh viên'],
            ];
            $typeId = array_key_first($types);
            foreach ($types as $candidateId => $candidate) {
                if (config('master_data.staff_by_department.'.$candidate['department_id'], [])) {
                    $typeId = $candidateId;
                    break;
                }
            }
            $type = $types[$typeId];
            $dept = $type['department_id'];
            $staff = config('master_data.staff_by_department.'.$dept, [])[0] ?? null;
            foreach ($examples as $i => [$state,$title]) {
                if (! $staff && $state !== 'new') {
                    continue;
                }
                $ticket = SupportRequest::firstOrCreate(['code' => 'DEMO-UNI-'.$user['id'].'-'.($i + 1)], [
                    'student_id' => $user['id'], 'department_id' => $dept, 'support_type_id' => $typeId, 'assigned_to' => $staff,
                    'assigned_at' => $staff ? now() : null, 'title' => '[Minh họa] '.$title, 'content' => 'Dữ liệu minh họa UniSupport để kiểm tra giao diện và quy trình hỗ trợ. Không phải hồ sơ sinh viên thực tế.',
                    'status' => $state, 'priority' => $i === 1 ? 'high' : 'normal', 'sla_flag' => 'on_time', 'sla_deadline_at' => now()->addDays(3),
                    'resolved_at' => in_array($state, ['resolved', 'closed']) ? now() : null, 'closed_at' => $state === 'closed' ? now() : null,
                    'rating' => $state === 'closed' ? 5 : null, 'rating_comment' => $state === 'closed' ? 'Hướng dẫn rõ ràng, phản hồi kịp thời.' : null,
                    'rated_at' => $state === 'closed' ? now() : null, 'created_at' => now()->subDays(5 - $i),
                ]);
                if ($ticket->wasRecentlyCreated) {
                    $ticket->forceFill(['created_at' => now()->subDays(5 - $i)])->save();
                    RequestStatusHistory::create(['request_id' => $ticket->id, 'from_status' => null, 'to_status' => $state, 'changed_by' => $user['id'], 'note' => 'Khởi tạo hồ sơ minh họa để trải nghiệm giao diện.', 'created_at' => $ticket->created_at]);
                }
            }
        });
    }
}
