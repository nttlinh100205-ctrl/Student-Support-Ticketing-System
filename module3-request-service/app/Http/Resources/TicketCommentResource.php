<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource cho TicketComment.
 *
 * Trả về thông tin comment + danh sách attachment (nếu đã eager-load).
 */
class TicketCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'request_id'  => $this->request_id,
            'user_id'     => $this->user_id,
            'user_name'   => $this->user_name,
            'user_role'   => $this->user_role,
            'body'        => $this->body,
            'is_internal' => $this->is_internal,
            'created_at'  => $this->created_at?->toJSON(),
            'updated_at'  => $this->updated_at?->toJSON(),

            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(fn ($a) => [
                    'id'            => $a->id,
                    'original_name' => $a->original_name,
                    'url'           => $a->url(),
                    'mime_type'     => $a->mime_type,
                    'size'          => $a->size,
                ]);
            }),
        ];
    }
}
