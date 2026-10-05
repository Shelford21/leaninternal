<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'process_id' => $this->process_id,
            'version_number' => $this->version_number,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'process' => new ProcessResource($this->whenLoaded('process')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
