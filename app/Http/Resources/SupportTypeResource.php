<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTypeResource extends JsonResource
{
    /**
     * Chuyển model SupportType thành dữ liệu JSON trả về API.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'code' => $this->code,

            'description' => $this->description,

            'department_id' => $this->department_id,

            'is_active' => (bool) $this->is_active,

            /**
             * Chỉ trả thông tin phòng ban
             * khi relation department đã được load.
             */
            'department' => $this->whenLoaded(
                'department',
                function () {
                    if (! $this->department) {
                        return null;
                    }

                    return [
                        'id' => $this->department->id,

                        'name' => $this->department->name,

                        'code' => $this->department->code,

                        'is_active' => (bool) $this->department->is_active,
                    ];
                }
            ),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}
