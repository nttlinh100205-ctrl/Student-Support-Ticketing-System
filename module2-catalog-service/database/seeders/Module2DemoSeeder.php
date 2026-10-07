<?php

namespace Database\Seeders;

use App\Models\SupportDepartment;
use App\Models\SupportFaq;
use App\Models\SupportType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dữ liệu DEMO cho Module 2: phòng ban, loại hỗ trợ, SLA, biểu mẫu và FAQ.
 *
 * Không chạy cùng DatabaseSeeder; chỉ chạy khi cần demo:
 *   php artisan db:seed --class=Module2DemoSeeder
 *
 * SLA và nội dung chỉ để minh họa, không phải quy định của trường.
 * Chạy lại nhiều lần không tạo trùng (tìm theo mã / câu hỏi).
 */
class Module2DemoSeeder extends Seeder
{
    private const DEPARTMENTS = [
        [
            'code' => 'CTSV',
            'name' => 'Phòng Công tác Sinh viên và Hỗ trợ',
            'description' => 'Tiếp nhận và xử lý các yêu cầu hỗ trợ sinh viên.',
        ],
        [
            'code' => 'DT',
            'name' => 'Phòng Đào Tạo',
            'description' => 'Tiếp nhận các yêu cầu về học tập và đăng ký học phần.',
        ],
        [
            'code' => 'KT',
            'name' => 'Phòng Khảo thí và Đảm bảo chất lượng',
            'description' => 'Tổ chức thi, phúc khảo, hoãn thi',
        ],
        [
            'code' => 'KHTC',
            'name' => 'Phòng Kế hoạch - Tài chính',
            'description' => 'Học phí, hoàn phí, biên lai thu',
        ],
        [
            'code' => 'TV',
            'name' => 'Thư viện',
            'description' => 'Thẻ thư viện, mượn trả tài liệu',
        ],
    ];

    private const SUPPORT_TYPES = [
        [
            'code' => 'XNSV',
            'name' => 'Cấp giấy xác nhận sinh viên',
            'department' => 'CTSV',
            'sla_days' => 2,
            'description' => 'Hỗ trợ cấp giấy xác nhận đang học tại trường.',
            'fields' => [
                [
                    'field_key' => 'student_code',
                    'label' => 'Mã sinh viên',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập mã sinh viên in trên thẻ sinh viên',
                ],
                [
                    'field_key' => 'purpose',
                    'label' => 'Mục đích xác nhận',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Vay vốn ngân hàng chính sách', 'Hoãn nghĩa vụ quân sự', 'Xin học bổng', 'Bổ sung hồ sơ khác'],
                    'help_text' => 'Chọn mục đích sử dụng giấy xác nhận',
                ],
                [
                    'field_key' => 'copies',
                    'label' => 'Số bản cần cấp',
                    'field_type' => 'number',
                    'is_required' => true,
                    'help_text' => 'Nhập số bản, ví dụ 2',
                ],
                [
                    'field_key' => 'submit_to',
                    'label' => 'Nơi nộp giấy xác nhận',
                    'field_type' => 'text',
                    'help_text' => 'Ví dụ: Ngân hàng Chính sách xã hội huyện..., UBND phường...',
                ],
            ],
        ],
        [
            'code' => 'HTHP',
            'name' => 'Hỗ trợ học phí',
            'department' => 'CTSV',
            'sla_days' => 7,
            'description' => 'Hỗ trợ các vấn đề liên quan đến học phí.',
            'fields' => [
                [
                    'field_key' => 'student_code',
                    'label' => 'Mã sinh viên',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập mã sinh viên in trên thẻ sinh viên',
                ],
                [
                    'field_key' => 'beneficiary_type',
                    'label' => 'Đối tượng',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Hộ nghèo', 'Hộ cận nghèo', 'Con thương binh, liệt sĩ', 'Dân tộc thiểu số vùng khó khăn', 'Khác'],
                    'help_text' => 'Chọn đối tượng chính sách của bạn',
                ],
                [
                    'field_key' => 'semester',
                    'label' => 'Học kỳ đề nghị hỗ trợ',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Học kỳ 1', 'Học kỳ 2', 'Học kỳ hè'],
                    'help_text' => 'Chọn học kỳ cần được hỗ trợ học phí',
                ],
                [
                    'field_key' => 'family_situation',
                    'label' => 'Hoàn cảnh gia đình',
                    'field_type' => 'textarea',
                    'help_text' => 'Mô tả ngắn gọn hoàn cảnh khó khăn của gia đình',
                ],
                [
                    'field_key' => 'proof_file',
                    'label' => 'Giấy tờ chứng minh',
                    'field_type' => 'file',
                    'is_required' => true,
                    'help_text' => 'Giấy chứng nhận hộ nghèo, cận nghèo hoặc giấy tờ chính sách khác',
                ],
            ],
        ],
        [
            'code' => 'DKHP',
            'name' => 'Đăng ký và điều chỉnh học phần',
            'department' => 'DT',
            'sla_days' => 3,
            'description' => 'Hỗ trợ đăng ký, hủy và điều chỉnh học phần.',
            'fields' => [
                [
                    'field_key' => 'student_code',
                    'label' => 'Mã sinh viên',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập mã sinh viên của bạn.',
                ],
                [
                    'field_key' => 'course_code',
                    'label' => 'Mã học phần',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập mã học phần cần điều chỉnh.',
                ],
                [
                    'field_key' => 'semester',
                    'label' => 'Học kỳ',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Học kỳ 1', 'Học kỳ 2', 'Học kỳ hè'],
                    'help_text' => 'Chọn học kỳ của học phần cần điều chỉnh',
                ],
                [
                    'field_key' => 'request_detail',
                    'label' => 'Nội dung đề nghị',
                    'field_type' => 'textarea',
                    'is_required' => true,
                    'help_text' => 'Mô tả nội dung và lý do điều chỉnh.',
                ],
                [
                    'field_key' => 'application_file',
                    'label' => 'Đơn đề nghị điều chỉnh học phần',
                    'field_type' => 'file',
                    'is_required' => true,
                    'help_text' => 'Giấy tờ cần nộp: đơn đề nghị điều chỉnh học phần.',
                ],
            ],
        ],
        [
            'code' => 'BD',
            'name' => 'Cấp bảng điểm',
            'department' => 'DT',
            'sla_days' => 5,
            'description' => 'Hỗ trợ yêu cầu cấp và tra cứu bảng điểm.',
            'fields' => [
                [
                    'field_key' => 'student_code',
                    'label' => 'Mã sinh viên',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập mã sinh viên của bạn.',
                ],
                [
                    'field_key' => 'transcript_scope',
                    'label' => 'Phạm vi bảng điểm',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Theo học kỳ', 'Theo năm học', 'Toàn khóa'],
                    'help_text' => 'Chọn phạm vi bảng điểm cần cấp',
                ],
                [
                    'field_key' => 'request_detail',
                    'label' => 'Nội dung đề nghị',
                    'field_type' => 'textarea',
                    'is_required' => true,
                    'help_text' => 'Ghi rõ học kỳ hoặc năm học nếu cần.',
                ],
            ],
        ],
        [
            'code' => 'PKBT',
            'name' => 'Phúc khảo bài thi',
            'department' => 'KT',
            'sla_days' => 7,
            'description' => 'Hỗ trợ sinh viên có thắc mắc về điểm số, yêu cầu xem lại bài thi hoặc đề nghị phúc khảo để đảm bảo kết quả đánh giá chính xác và công bằng.',
            'fields' => [
                [
                    'field_key' => 'student_code',
                    'label' => 'Mã sinh viên',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập mã sinh viên in trên thẻ sinh viên',
                ],
                [
                    'field_key' => 'subject_name',
                    'label' => 'Tên học phần',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Ghi đúng tên học phần như trên bảng điểm',
                ],
                [
                    'field_key' => 'semester',
                    'label' => 'Học kỳ',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Học kỳ 1', 'Học kỳ 2', 'Học kỳ hè'],
                    'help_text' => 'Chọn học kỳ của môn thi cần phúc khảo',
                ],
                [
                    'field_key' => 'exam_date',
                    'label' => 'Ngày thi',
                    'field_type' => 'date',
                    'help_text' => 'Chọn ngày bạn dự thi môn này',
                ],
                [
                    'field_key' => 'current_score',
                    'label' => 'Điểm hiện tại',
                    'field_type' => 'number',
                    'is_required' => true,
                    'help_text' => 'Nhập điểm thi đã được công bố, ví dụ 4.5',
                ],
                [
                    'field_key' => 'reason',
                    'label' => 'Lý do phúc khảo',
                    'field_type' => 'textarea',
                    'help_text' => 'Nêu rõ câu hoặc phần bạn muốn được chấm lại',
                ],
                [
                    'field_key' => 'fee_receipt',
                    'label' => 'Biên lai nộp lệ phí',
                    'field_type' => 'file',
                    'is_required' => true,
                    'help_text' => 'Tải ảnh chụp hoặc file PDF biên lai đã nộp lệ phí phúc khảo',
                ],
            ],
        ],
        [
            'code' => 'HOANTHI',
            'name' => 'Hoãn thi',
            'department' => 'KT',
            'sla_days' => 3,
            'description' => 'Hỗ trợ sinh viên trong các trường hợp cần xin hoãn thi hoặc xử lý các vấn đề liên quan đến kỳ thi theo quy định của trường.',
            'fields' => [
                [
                    'field_key' => 'student_code',
                    'label' => 'Mã sinh viên',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập mã sinh viên in trên thẻ sinh viên',
                ],
                [
                    'field_key' => 'subject_name',
                    'label' => 'Tên học phần',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Ghi tên học phần muốn hoãn',
                ],
                [
                    'field_key' => 'exam_date',
                    'label' => 'Ngày thi',
                    'field_type' => 'date',
                    'is_required' => true,
                    'help_text' => 'Chọn ngày thi theo lịch thi chính thức',
                ],
                [
                    'field_key' => 'postpone_reason',
                    'label' => 'Lý do hoãn thi',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Ốm đau, tai nạn', 'Trùng lịch thi', 'Việc gia đình', 'Lý do khác'],
                    'help_text' => 'Chọn lý do phù hợp nhất với trường hợp của bạn',
                ],
                [
                    'field_key' => 'detail',
                    'label' => 'Mô tả chi tiết',
                    'field_type' => 'textarea',
                    'help_text' => 'Trình bày ngắn gọn hoàn cảnh cụ thể',
                ],
                [
                    'field_key' => 'proof_file',
                    'label' => 'Giấy tờ chứng minh',
                    'field_type' => 'file',
                    'is_required' => true,
                    'help_text' => 'Giấy khám bệnh, lịch thi bị trùng hoặc giấy xác nhận liên quan',
                ],
            ],
        ],
        [
            'code' => 'HOANHP',
            'name' => 'Hoàn học phí',
            'department' => 'KHTC',
            'sla_days' => 10,
            'description' => 'Hỗ trợ sinh viên đề nghị hoàn học phí khi đóng thừa, hủy học phần hoặc bảo lưu.',
            'fields' => [
                [
                    'field_key' => 'student_code',
                    'label' => 'Mã sinh viên',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập mã sinh viên in trên thẻ sinh viên',
                ],
                [
                    'field_key' => 'semester',
                    'label' => 'Học kỳ',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Học kỳ 1', 'Học kỳ 2', 'Học kỳ hè'],
                    'help_text' => 'Chọn học kỳ đã đóng học phí cần hoàn',
                ],
                [
                    'field_key' => 'amount',
                    'label' => 'Số tiền đề nghị hoàn',
                    'field_type' => 'number',
                    'is_required' => true,
                    'help_text' => 'Nhập số tiền bằng số, không ghi dấu chấm, ví dụ 1500000',
                ],
                [
                    'field_key' => 'refund_reason',
                    'label' => 'Lý do hoàn',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Đóng thừa học phí', 'Hủy học phần', 'Bảo lưu, tạm dừng học', 'Lý do khác'],
                    'help_text' => 'Chọn lý do đề nghị hoàn học phí',
                ],
                [
                    'field_key' => 'bank_name',
                    'label' => 'Ngân hàng',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Vietcombank', 'Vietinbank', 'MB Bank', 'Ngân hàng khác'],
                    'help_text' => 'Chọn ngân hàng của tài khoản nhận tiền hoàn (tài khoản đứng tên sinh viên)',
                ],
                [
                    'field_key' => 'bank_account',
                    'label' => 'Số tài khoản',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập số tài khoản ngân hàng đứng tên sinh viên',
                ],
                [
                    'field_key' => 'receipt',
                    'label' => 'Biên lai đóng học phí',
                    'field_type' => 'file',
                    'is_required' => true,
                    'help_text' => 'Tải ảnh chụp hoặc file PDF biên lai đóng học phí',
                ],
            ],
        ],
        [
            'code' => 'CLTTV',
            'name' => 'Cấp lại thẻ thư viện',
            'department' => 'TV',
            'sla_days' => 2,
            'description' => 'Hỗ trợ sinh viên khi thẻ thư viện bị mất, hỏng, hết hạn hoặc cần cấp lại để tiếp tục sử dụng dịch vụ của thư viện.',
            'fields' => [
                [
                    'field_key' => 'student_code',
                    'label' => 'Mã sinh viên',
                    'field_type' => 'text',
                    'is_required' => true,
                    'help_text' => 'Nhập mã sinh viên in trên thẻ sinh viên',
                ],
                [
                    'field_key' => 'reissue_reason',
                    'label' => 'Lý do cấp lại',
                    'field_type' => 'select',
                    'is_required' => true,
                    'options' => ['Mất thẻ', 'Thẻ bị hỏng', 'Thay đổi thông tin'],
                    'help_text' => 'Chọn lý do cần cấp lại thẻ',
                ],
                [
                    'field_key' => 'photo',
                    'label' => 'Ảnh thẻ',
                    'field_type' => 'file',
                    'is_required' => true,
                    'help_text' => 'Ảnh 3x4 nền trắng, chụp trong vòng 6 tháng',
                ],
                [
                    'field_key' => 'note',
                    'label' => 'Ghi chú',
                    'field_type' => 'textarea',
                    'help_text' => 'Thông tin thêm nếu có, ví dụ thời điểm làm mất thẻ',
                ],
            ],
        ],
    ];

    private const FAQS = [
        [
            'department' => 'DT',
            'support_type' => 'DKHP',
            'question' => 'Tôi cần cung cấp thông tin gì khi đề nghị điều chỉnh học phần?',
            'answer' => 'Bạn cần nhập mã sinh viên, mã học phần, học kỳ, nội dung đề nghị và đính kèm đơn đề nghị điều chỉnh học phần theo yêu cầu của biểu mẫu.',
            'sort_order' => 1,
        ],
        [
            'department' => 'DT',
            'support_type' => 'BD',
            'question' => 'Tôi cần cung cấp thông tin gì để đề nghị cấp bảng điểm?',
            'answer' => 'Bạn cần nhập mã sinh viên, chọn phạm vi bảng điểm và ghi rõ nội dung đề nghị. Nếu chọn theo học kỳ hoặc năm học, hãy nêu rõ thời gian cần cấp.',
            'sort_order' => 1,
        ],
        [
            'department' => 'KT',
            'question' => 'Phòng Khảo thí làm việc giờ nào?',
            'answer' => 'Từ thứ 2 đến thứ 6, sáng 7h30–11h30, chiều 13h30–17h00.',
            'sort_order' => 1,
        ],
        [
            'department' => 'KT',
            'question' => 'Lịch thi học kỳ hè đã có chưa?',
            'answer' => 'Chưa có, sẽ cập nhật sau.',
            'sort_order' => 2,
            'is_active' => false,
        ],
        [
            'department' => 'KT',
            'support_type' => 'PKBT',
            'question' => 'Sau bao lâu thì có kết quả phúc khảo?',
            'answer' => 'Khoảng 7 ngày kể từ khi nộp đủ hồ sơ. Kết quả được thông báo qua hệ thống.',
            'sort_order' => 1,
        ],
        [
            'department' => 'KT',
            'support_type' => 'PKBT',
            'question' => 'Điểm phúc khảo có thể thấp hơn điểm cũ không?',
            'answer' => 'Có. Điểm sau phúc khảo là điểm chính thức, có thể tăng, giữ nguyên hoặc giảm.',
            'sort_order' => 2,
        ],
        [
            'department' => 'KT',
            'support_type' => 'HOANTHI',
            'question' => 'Khi nào phải nộp đơn hoãn thi?',
            'answer' => 'Trước ngày thi ít nhất 3 ngày, trừ trường hợp ốm đau, tai nạn đột xuất.',
            'sort_order' => 1,
        ],
        [
            'department' => 'KHTC',
            'question' => 'Tôi có thể đóng học phí bằng cách nào?',
            'answer' => 'Chuyển khoản theo thông tin trên thông báo học phí hoặc nộp trực tiếp tại phòng Kế hoạch – Tài chính.',
            'sort_order' => 1,
        ],
        [
            'department' => 'KHTC',
            'support_type' => 'HOANHP',
            'question' => 'Tiền hoàn được chuyển về đâu?',
            'answer' => 'Vào tài khoản ngân hàng đứng tên sinh viên đã khai trong đơn.',
            'sort_order' => 1,
        ],
        [
            'department' => 'TV',
            'support_type' => 'CLTTV',
            'question' => 'Trong thời gian chờ cấp thẻ có mượn sách được không?',
            'answer' => 'Được đọc tại chỗ bằng thẻ sinh viên, nhưng chưa mượn về nhà được.',
            'sort_order' => 1,
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $departmentIds = [];

            foreach (self::DEPARTMENTS as $department) {
                $departmentIds[$department['code']] = SupportDepartment::updateOrCreate(
                    ['code' => $department['code']],
                    $department + ['is_active' => true]
                )->id;
            }

            $typeIds = [];

            foreach (self::SUPPORT_TYPES as $item) {
                $supportType = SupportType::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'name' => $item['name'],
                        'department_id' => $departmentIds[$item['department']],
                        'sla_days' => $item['sla_days'],
                        'description' => $item['description'],
                        'is_active' => true,
                    ]
                );

                $typeIds[$item['code']] = $supportType->id;

                foreach ($item['fields'] as $index => $field) {
                    $supportType->fields()->updateOrCreate(
                        ['field_key' => $field['field_key']],
                        [
                            'label' => $field['label'],
                            'field_type' => $field['field_type'],
                            'is_required' => $field['is_required'] ?? false,
                            'options' => $field['options'] ?? null,
                            'help_text' => $field['help_text'] ?? null,
                            'sort_order' => $index + 1,
                            'is_active' => true,
                        ]
                    );
                }
            }

            foreach (self::FAQS as $faq) {
                SupportFaq::updateOrCreate(
                    [
                        'department_id' => $departmentIds[$faq['department']],
                        'question' => $faq['question'],
                    ],
                    [
                        'support_type_id' => isset($faq['support_type'])
                            ? $typeIds[$faq['support_type']]
                            : null,
                        'answer' => $faq['answer'],
                        'sort_order' => $faq['sort_order'],
                        'is_active' => $faq['is_active'] ?? true,
                    ]
                );
            }
        });

        $this->command?->info('Đã nạp dữ liệu demo Module 2.');
    }
}
