<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportDepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,

            'staff_count' => $this->when(
                isset($this->staff_count),
                $this->staff_count
            ),

            'heads_count' => $this->when(
                isset($this->heads_count),
                $this->heads_count
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
