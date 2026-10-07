<?php

namespace Database\Seeders;

use App\Models\Rating;
use Illuminate\Database\Seeder;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $samples = [
            ['request_id' => 101, 'student_id' => 12, 'department_id' => 3, 'support_type_id' => 2, 'rating' => 5, 'comment' => 'Xử lý rất nhanh và tận tình.'],
            ['request_id' => 103, 'student_id' => 14, 'department_id' => 3, 'support_type_id' => 2, 'rating' => 5, 'comment' => 'Rất hài lòng, nhận được bảng điểm trong ngày.'],
            ['request_id' => 104, 'student_id' => 15, 'department_id' => 1, 'support_type_id' => 3, 'rating' => 5, 'comment' => 'Thầy cô phòng đào tạo hỗ trợ rất chu đáo.'],
            ['request_id' => 105, 'student_id' => 16, 'department_id' => 2, 'support_type_id' => 4, 'rating' => 4, 'comment' => 'Giải thích thắc mắc học phí rõ ràng.'],
            ['request_id' => 109, 'student_id' => 20, 'department_id' => 3, 'support_type_id' => 2, 'rating' => 5, 'comment' => 'Thủ tục nhanh gọn, cán bộ nhiệt tình.'],
            ['request_id' => 111, 'student_id' => 22, 'department_id' => 1, 'support_type_id' => 3, 'rating' => 4, 'comment' => 'Đã chuyển được lớp phù hợp.'],
            ['request_id' => 112, 'student_id' => 23, 'department_id' => 5, 'support_type_id' => 5, 'rating' => 3, 'comment' => 'Thời gian chấm phúc khảo hơi lâu một chút.'],
            ['request_id' => 113, 'student_id' => 24, 'department_id' => 4, 'support_type_id' => 6, 'rating' => 5, 'comment' => 'Giấy giới thiệu làm nhanh, kịp nộp công ty.'],
            ['request_id' => 115, 'student_id' => 26, 'department_id' => 3, 'support_type_id' => 1, 'rating' => 5, 'comment' => 'Thẻ in lại rất đẹp và rõ nét.'],
            ['request_id' => 116, 'student_id' => 27, 'department_id' => 3, 'support_type_id' => 2, 'rating' => 5, 'comment' => 'Xác nhận nhanh, phục vụ tốt.'],
            ['request_id' => 117, 'student_id' => 28, 'department_id' => 2, 'support_type_id' => 4, 'rating' => 4, 'comment' => 'Đã sửa lại biên lai chính xác.'],
            ['request_id' => 120, 'student_id' => 31, 'department_id' => 3, 'support_type_id' => 2, 'rating' => 5, 'comment' => 'Cán bộ CTSV hỗ trợ giấy tờ xin học bổng rất tận tâm.'],
        ];

        foreach ($samples as $sample) {
            Rating::updateOrCreate(
                ['request_id' => $sample['request_id']],
                $sample
            );
        }
    }
}
