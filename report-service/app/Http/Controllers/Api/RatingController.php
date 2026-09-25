<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\QueryRatingRequest;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Resources\RatingResource;
use App\Http\Responses\ApiResponse;
use App\Services\RatingService;
use Illuminate\Validation\ValidationException;

class RatingController extends Controller
{
    public function __construct(
        private RatingService $ratingService,
        private AuthContext $auth
    ) {}

    /**
     * Lấy danh sách đánh giá kèm bộ lọc.
     */
    public function index(QueryRatingRequest $request)
    {
        $ratings = $this->ratingService->getRatings($request->validated());

        return ApiResponse::success(RatingResource::collection($ratings)->response()->getData(true));
    }

    /**
     * Sinh viên gửi đánh giá cho yêu cầu đã hoàn thành.
     */
    public function store(StoreRatingRequest $request)
    {
        $studentId = $this->auth->userId();

        if (! $studentId) {
            return ApiResponse::error('Không tìm thấy thông tin định danh sinh viên.', 401);
        }

        try {
            $rating = $this->ratingService->createRating($studentId, $request->validated());

            return ApiResponse::success(new RatingResource($rating), 'Gửi đánh giá thành công.', 201);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Xem thống kê tổng hợp đánh giá.
     */
    public function summary(QueryRatingRequest $request)
    {
        $summary = $this->ratingService->getRatingSummary($request->validated());

        return ApiResponse::success($summary);
    }
}
