<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'factory_id' => $this->factory_id,
            'department_name' => $this->department_name,
            'desription' => $this->desription,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'factory' => new FactoryResource($this->whenLoaded('factory')),
        ];
    }
}
