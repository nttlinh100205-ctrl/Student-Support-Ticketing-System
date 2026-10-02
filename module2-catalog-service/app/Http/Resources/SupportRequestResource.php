<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ép format field ra ngoài luôn giống nhau (snake_case) — để FE viết
 * 1 hàm xử lý chung cho mọi API (Mục 5.1 tài liệu).
 */
class SupportRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'student_id' => $this->student_id,
            'department_id' => $this->department_id,
            'support_type_id' => $this->support_type_id,
            'assigned_to' => $this->assigned_to,
            'title' => $this->title,
            'content' => $this->content,
            'priority' => $this->priority?->value,
            'status' => $this->status?->value,
            'cancelled_reason' => $this->cancelled_reason,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
