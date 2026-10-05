<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePtmsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'article_id' => 'required|exists:articles,id',
            'process_version_id' => 'required|exists:process_versions,id',
            'operator_id' => 'required|exists:operators,id',
            'factory_id' => 'required|exists:factories,id',
            'department_id' => 'required|exists:departments,id',
            'line_id' => 'required|exists:production_lines,id',
            'machine_name' => 'nullable',
            'feed_type' => 'nullable',
            'rpm' => 'nullable|numeric',
            'stitch_per_cm' => 'nullable|numeric',
            'seam_width' => 'nullable|numeric',
            'machine_delay_percent' => 'nullable|numeric',
            'contingency_percent' => 'nullable|numeric',
            'ra_percent' => 'nullable|numeric',
            'machining_tmu' => 'nullable|numeric',
            'handling_tmu' => 'nullable|numeric',
            'bundle_tmu' => 'nullable|numeric',
            'total_tmu' => 'nullable|numeric',
            'bms' => 'nullable|numeric',
            'smv' => 'nullable|numeric',
            'status' => 'in:draft,final,archived',
        ];
    }
}
