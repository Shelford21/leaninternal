<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GsdElementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gsd_category_id' => $this->gsd_category_id,
            'element_name' => $this->element_name,
            'description' => $this->description,
            'code' => $this->code,
            'tmu' => $this->tmu,
            'seconds' => $this->seconds,
            'motion_sequence' => $this->motion_sequence,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'gsd_category' => new GsdCategoryResource($this->whenLoaded('gsdCategory')),
        ];
    }
}
