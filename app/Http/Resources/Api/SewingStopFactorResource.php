<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SewingStopFactorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'factor_name' => $this->factor_name,
            'description' => $this->description,
            'tolerance' => $this->tolerance,
            'factor_value' => $this->factor_value,
            'code' => $this->code,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
