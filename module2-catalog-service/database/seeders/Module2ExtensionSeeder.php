<?php

namespace Database\Seeders;

use App\Models\SupportFaq;
use App\Models\SupportType;
use App\Models\SupportTypeField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Module2ExtensionSeeder extends Seeder
{
    /**
     * Dữ liệu minh họa cho Module 2.
     * SLA dưới đây là số ngày mẫu, không phải quy định của trường.
     */
    public function run(): void
    {
        $samples = [
            'DKHP' => [
                'sla_days' => 3,

                'fields' => [
                    [
                        'field_key' => 'student_code',
                        'label' => 'Mã sinh viên',
                        'field_type' => 'text',
                        'help_text' => 'Nhập mã sinh viên của bạn.',
                    ],
                    [
                        'field_key' => 'course_code',
                        'label' => 'Mã học phần',
                        'field_type' => 'text',
                        'help_text' => 'Nhập mã học phần cần điều chỉnh.',
                    ],
                    [
                        'field_key' => 'semester',
                        'label' => 'Học kỳ',
                        'field_type' => 'select',
                        'options' => [
                            'Học kỳ 1',
                            'Học kỳ 2',
                            'Học kỳ hè',
                        ],
                    ],
                    [
                        'field_key' => 'request_detail',
                        'label' => 'Nội dung đề nghị',
                        'field_type' => 'textarea',
                        'help_text' => 'Mô tả nội dung và lý do điều chỉnh.',
                    ],
                    [
                        'field_key' => 'application_file',
                        'label' => 'Đơn đề nghị điều chỉnh học phần',
                        'field_type' => 'file',
                        'help_text' => 'Giấy tờ cần nộp: đơn đề nghị điều chỉnh học phần.',
                    ],
                ],

                'faq' => [
                    'question' => 'Tôi cần cung cấp thông tin gì khi đề nghị điều chỉnh học phần?',
                    'answer' => 'Bạn cần nhập mã sinh viên, mã học phần, học kỳ, nội dung đề nghị và đính kèm đơn đề nghị điều chỉnh học phần theo yêu cầu của biểu mẫu.',
                ],
            ],

            'BD' => [
                'sla_days' => 5,

                'fields' => [
                    [
                        'field_key' => 'student_code',
                        'label' => 'Mã sinh viên',
                        'field_type' => 'text',
                        'help_text' => 'Nhập mã sinh viên của bạn.',
                    ],
                    [
                        'field_key' => 'transcript_scope',
                        'label' => 'Phạm vi bảng điểm',
                        'field_type' => 'select',
                        'options' => [
                            'Theo học kỳ',
                            'Theo năm học',
                            'Toàn khóa',
                        ],
                    ],
                    [
                        'field_key' => 'request_detail',
                        'label' => 'Nội dung đề nghị',
                        'field_type' => 'textarea',
                        'help_text' => 'Ghi rõ học kỳ hoặc năm học nếu cần.',
                    ],
                ],

                'faq' => [
                    'question' => 'Tôi cần cung cấp thông tin gì để đề nghị cấp bảng điểm?',
                    'answer' => 'Bạn cần nhập mã sinh viên, chọn phạm vi bảng điểm và ghi rõ nội dung đề nghị. Nếu chọn theo học kỳ hoặc năm học, hãy nêu rõ thời gian cần cấp.',
                ],
            ],
        ];

        foreach ($samples as $code => $sample) {
            $supportType = SupportType::query()
                ->where('code', $code)
                ->first();

            if (! $supportType) {
                $this->command?->warn(
                    "Bỏ qua {$code}: chưa có loại hỗ trợ mang mã này."
                );

                continue;
            }

            if (! $supportType->department_id) {
                $this->command?->warn(
                    "Bỏ qua {$code}: loại hỗ trợ chưa có phòng phụ trách."
                );

                continue;
            }

            DB::transaction(function () use ($supportType, $sample) {
                // Chỉ điền SLA khi chưa cấu hình.
                SupportType::query()
                    ->whereKey($supportType->id)
                    ->whereNull('sla_days')
                    ->update([
                        'sla_days' => $sample['sla_days'],
                    ]);

                foreach ($sample['fields'] as $index => $field) {
                    // Giữ nguyên trường đã có cùng field_key.
                    SupportTypeField::firstOrCreate(
                        [
                            'support_type_id' => $supportType->id,
                            'field_key' => $field['field_key'],
                        ],
                        [
                            'label' => $field['label'],
                            'field_type' => $field['field_type'],
                            'is_required' => true,
                            'options' => $field['options'] ?? null,
                            'help_text' => $field['help_text'] ?? null,
                            'sort_order' => $index + 1,
                            'is_active' => true,
                        ]
                    );
                }

                /*
                 * Chỉ tạo FAQ mẫu nếu loại hỗ trợ chưa có FAQ nào.
                 * Vì vậy, sửa nội dung FAQ rồi chạy lại seeder
                 * cũng không tạo thêm câu hỏi mẫu.
                 */
                $hasFaq = SupportFaq::query()
                    ->where('support_type_id', $supportType->id)
                    ->exists();

                if (! $hasFaq) {
                    SupportFaq::create([
                        'department_id' => $supportType->department_id,
                        'support_type_id' => $supportType->id,
                        'question' => $sample['faq']['question'],
                        'answer' => $sample['faq']['answer'],
                        'sort_order' => 1,
                        'is_active' => true,
                    ]);
                }
            });

            $this->command?->info(
                "Đã bổ sung dữ liệu còn thiếu cho {$code}; giữ nguyên dữ liệu đã có."
            );
        }
    }
}