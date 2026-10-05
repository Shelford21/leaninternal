<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'process_name' => $this->process_name,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'versions' => ProcessVersionResource::collection($this->whenLoaded('versions')),
            'latest_version' => new ProcessVersionResource($this->whenLoaded('latestVersion')),
        ];
    }
}
