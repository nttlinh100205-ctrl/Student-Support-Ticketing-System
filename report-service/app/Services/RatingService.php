<?php

namespace App\Services;

use App\Contracts\RequestServiceClientInterface;
use App\Models\Rating;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class RatingService
{
    public function __construct(
        private RequestServiceClientInterface $requestClient
    ) {}

    /**
     * Tạo đánh giá chất lượng cho yêu cầu hỗ trợ.
     *
     * @param  array{request_id: int, rating: int, comment?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function createRating(int $studentId, array $data): Rating
    {
        $requestId = (int) $data['request_id'];
        $requestData = $this->requestClient->getRequestById($requestId);

        if (! $requestData) {
            throw ValidationException::withMessages([
                'request_id' => ['Yêu cầu hỗ trợ không tồn tại trong hệ thống.'],
            ]);
        }

        // Kiểm tra quyền: chỉ sinh viên sở hữu yêu cầu mới được đánh giá
        if (isset($requestData['student_id']) && (int) $requestData['student_id'] !== $studentId) {
            throw ValidationException::withMessages([
                'request_id' => ['Bạn chỉ có thể đánh giá yêu cầu do chính mình tạo.'],
            ]);
        }

        // Kiểm tra trạng thái yêu cầu: chỉ được đánh giá khi đã hoàn thành (resolved hoặc closed)
        $status = $requestData['status'] ?? '';
        if (! in_array($status, ['resolved', 'closed'], true)) {
            throw ValidationException::withMessages([
                'request_id' => ['Yêu cầu hỗ trợ chưa được hoàn tất xử lý (trạng thái hiện tại: '.$status.').'],
            ]);
        }

        // Kiểm tra nếu yêu cầu đã được đánh giá trước đó
        $existing = Rating::where('request_id', $requestId)->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'request_id' => ['Yêu cầu này đã được đánh giá trước đó.'],
            ]);
        }

        return Rating::create([
            'request_id' => $requestId,
            'student_id' => $studentId,
            'department_id' => $requestData['department_id'] ?? null,
            'support_type_id' => $requestData['support_type_id'] ?? null,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }

    /**
     * Lấy danh sách đánh giá có phân trang và bộ lọc.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getRatings(array $filters = []): LengthAwarePaginator
    {
        $query = Rating::query();

        if (! empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (! empty($filters['support_type_id'])) {
            $query->where('support_type_id', (int) $filters['support_type_id']);
        }

        if (! empty($filters['rating'])) {
            $query->where('rating', (int) $filters['rating']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Lấy báo cáo tổng hợp đánh giá chất lượng và độ hài lòng.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getRatingSummary(array $filters = []): array
    {
        $query = Rating::query();

        if (! empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (! empty($filters['support_type_id'])) {
            $query->where('support_type_id', (int) $filters['support_type_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $ratings = $query->get();
        $total = $ratings->count();
        $average = $total > 0 ? round($ratings->avg('rating'), 2) : 0.0;

        $byStars = [
            1 => $ratings->where('rating', 1)->count(),
            2 => $ratings->where('rating', 2)->count(),
            3 => $ratings->where('rating', 3)->count(),
            4 => $ratings->where('rating', 4)->count(),
            5 => $ratings->where('rating', 5)->count(),
        ];

        $byDepartment = $ratings->groupBy('department_id')->map(function ($items, $deptId) {
            return [
                'department_id' => (int) $deptId,
                'total_ratings' => $items->count(),
                'average_rating' => round($items->avg('rating'), 2),
            ];
        })->values()->toArray();

        return [
            'total_ratings' => $total,
            'average_rating' => $average,
            'by_stars' => $byStars,
            'by_department' => $byDepartment,
        ];
    }
}
