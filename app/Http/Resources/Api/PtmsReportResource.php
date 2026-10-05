<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PtmsReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_number' => $this->report_number,
            'article_id' => $this->article_id,
            'process_version_id' => $this->process_version_id,
            'operator_id' => $this->operator_id,
            'factory_id' => $this->factory_id,
            'department_id' => $this->department_id,
            'line_id' => $this->line_id,
            'created_by' => $this->created_by,
            'machine_name' => $this->machine_name,
            'feed_type' => $this->feed_type,
            'rpm' => $this->rpm,
            'stitch_per_cm' => $this->stitch_per_cm,
            'seam_width' => $this->seam_width,
            'machine_delay_percent' => $this->machine_delay_percent,
            'contingency_percent' => $this->contingency_percent,
            'ra_percent' => $this->ra_percent,
            'machining_tmu' => $this->machining_tmu,
            'handling_tmu' => $this->handling_tmu,
            'bundle_tmu' => $this->bundle_tmu,
            'total_tmu' => $this->total_tmu,
            'bms' => $this->bms,
            'smv' => $this->smv,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'article' => new ArticleResource($this->whenLoaded('article')),
            'process_version' => new ProcessVersionResource($this->whenLoaded('processVersion')),
            'operator' => new OperatorResource($this->whenLoaded('operator')),
            'factory' => new FactoryResource($this->whenLoaded('factory')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'production_line' => new ProductionLineResource($this->whenLoaded('productionLine')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
