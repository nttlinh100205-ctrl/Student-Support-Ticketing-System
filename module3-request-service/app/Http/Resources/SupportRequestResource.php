<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'assigned_at' => $this->assigned_at?->toJSON(),
            'resolved_at' => $this->resolved_at?->toJSON(),
            'closed_at' => $this->closed_at?->toJSON(),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),

            // --- SLA ---
            'sla_deadline_at'     => $this->sla_deadline_at?->toJSON(),
            'sla_flag'            => $this->sla_flag?->value ?? 'on_time',
            'sla_flag_label'      => $this->sla_flag?->label() ?? 'Đúng hạn',
            'sla_remaining_hours' => $this->slaRemainingHours(),

            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'original_name' => $a->original_name,
                    'url' => $a->url(),
                    'mime_type' => $a->mime_type,
                    'size' => $a->size,
                ]);
            }),

            'comments_count' => $this->whenCounted('comments'),
        ];
    }
}

