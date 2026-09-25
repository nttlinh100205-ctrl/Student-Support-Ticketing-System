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
            ['request_id' => 103, 'student_id' => 14, 'department_id' => 3, 'support_type_id' => 2, 'rating' => 4, 'comment' => 'Hài lòng với dịch vụ.'],
            ['request_id' => 104, 'student_id' => 15, 'department_id' => 1, 'support_type_id' => 3, 'rating' => 5, 'comment' => 'Tuyệt vời, cảm ơn thầy cô!'],
            ['request_id' => 105, 'student_id' => 16, 'department_id' => 2, 'support_type_id' => 4, 'rating' => 3, 'comment' => 'Cần cải thiện thời gian phản hồi.'],
            ['request_id' => 106, 'student_id' => 17, 'department_id' => 1, 'support_type_id' => 5, 'rating' => 4, 'comment' => 'Giải đáp thắc mắc rõ ràng.'],
        ];

        foreach ($samples as $sample) {
            Rating::updateOrCreate(
                ['request_id' => $sample['request_id']],
                $sample
            );
        }
    }
}
