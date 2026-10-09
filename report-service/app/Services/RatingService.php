<?php

namespace App\Services;

use App\Contracts\RequestServiceClientInterface;
use App\Models\Rating;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
        if (! config('services.request_service.mock')) {
            $item = app(ServiceClient::class)->get(config('services.request_service.url'), '/api/requests/'.$data['request_id'].'/rating', [
                'rating' => $data['rating'], 'comment' => $data['comment'] ?? null,
                'rating_attitude' => $data['rating_attitude'] ?? null,
                'rating_speed' => $data['rating_speed'] ?? null,
                'rating_quality' => $data['rating_quality'] ?? null,
            ], 'POST')['data'];

            return $this->fromTicket($item);
        }
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
        if (! config('services.request_service.mock')) {
            $ratings = $this->remoteRatings($filters)->sortByDesc('created_at')->values();
            $page = max(1, (int) request('page', 1));
            $perPage = (int) ($filters['per_page'] ?? 15);

            return new LengthAwarePaginator($ratings->forPage($page, $perPage)->values(), $ratings->count(), $perPage, $page, ['path' => request()->url()]);
        }
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

        $ratings = config('services.request_service.mock') ? $query->get() : $this->remoteRatings($filters);
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

        $allRequests = collect($this->requestClient->getRequests())->keyBy('id');
        $byStaff = $ratings->groupBy(function ($r) use ($allRequests) {
            $req = $allRequests->get($r->request_id);

            return $req['staff_id'] ?? 0;
        })->filter(fn ($items, $staffId) => $staffId > 0)->map(function ($items, $staffId) use ($allRequests) {
            $firstReq = $allRequests->first(fn ($req) => ($req['staff_id'] ?? null) == $staffId);

            return [
                'staff_id' => (int) $staffId,
                'staff_name' => $firstReq['staff_name'] ?? ('Cán bộ #'.$staffId),
                'total_ratings' => $items->count(),
                'average_rating' => round($items->avg('rating'), 2),
            ];
        })->values()->toArray();

        return [
            'total_ratings' => $total,
            'average_rating' => $average,
            'by_stars' => $byStars,
            'by_department' => $byDepartment,
            'by_staff' => $byStaff,
        ];
    }

    private function fromTicket(array $item): Rating
    {
        return (new Rating)->forceFill([
            'id' => $item['id'], 'request_id' => $item['id'], 'student_id' => $item['student_id'],
            'department_id' => $item['department_id'], 'support_type_id' => $item['support_type_id'],
            'rating' => $item['rating'], 'comment' => $item['rating_comment'] ?? null,
            'created_at' => $item['rated_at'], 'updated_at' => $item['rated_at'],
        ]);
    }

    private function remoteRatings(array $filters): Collection
    {
        $ticketFilters = array_diff_key($filters, array_flip(['from_date', 'to_date', 'rating', 'per_page']));

        return collect($this->requestClient->getRequests($ticketFilters))
            ->filter(fn ($item) => ! empty($item['rating']))
            ->map(fn ($item) => $this->fromTicket($item))
            ->filter(function ($rating) use ($filters) {
                if (! empty($filters['rating']) && $rating->rating !== (int) $filters['rating']) {
                    return false;
                }
                if (! empty($filters['support_type_id']) && $rating->support_type_id !== (int) $filters['support_type_id']) {
                    return false;
                }
                $date = $rating->created_at?->toDateString();

                return (empty($filters['from_date']) || $date >= $filters['from_date'])
                    && (empty($filters['to_date']) || $date <= $filters['to_date']);
            });
    }
}
