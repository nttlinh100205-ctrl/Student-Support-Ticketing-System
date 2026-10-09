<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupportRequestResource;
use App\Models\SupportRequest;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request, SupportRequest $supportRequest, AuthContext $auth)
    {
        abort_unless($auth->role() === 'student' && $supportRequest->student_id === $auth->userId(), 403);
        $data = $request->validate([
            'rating' => 'required|integer|between:1,5', 'comment' => 'nullable|string|max:1000',
            'rating_attitude' => 'nullable|integer|between:1,5',
            'rating_speed' => 'nullable|integer|between:1,5',
            'rating_quality' => 'nullable|integer|between:1,5',
        ]);
        $updated = SupportRequest::whereKey($supportRequest->id)->where('status', 'closed')->whereNull('rating')
            ->update(['rating' => $data['rating'], 'rating_comment' => $data['comment'] ?? null, 'rated_at' => now(),
                'rating_attitude' => $data['rating_attitude'] ?? null, 'rating_speed' => $data['rating_speed'] ?? null,
                'rating_quality' => $data['rating_quality'] ?? null]);
        abort_unless($updated, 409, 'Yêu cầu chưa đóng hoặc đã được đánh giá.');

        return response()->json(['success' => true, 'data' => new SupportRequestResource($supportRequest->fresh()), 'message' => null], 201);
    }
}
