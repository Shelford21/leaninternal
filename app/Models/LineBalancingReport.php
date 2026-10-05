<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineBalancingReport extends Model
{
    use HasFactory;

    protected $table = 'line_balancing_reports';

    protected $fillable = [
        'factory_id',
        'article_id',
        'line_id',
        'report_name',
        'target_output_per_hour',
        'output_actual',
        'working_hours_per_day',
        'allowance_percent',
        'update_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'target_output_per_hour' => 'integer',
        'output_actual' => 'integer',
        'working_hours_per_day' => 'decimal:2',
        'allowance_percent' => 'decimal:2',
        'update_date' => 'date',
        'status' => 'string',
    ];

    public function factory()
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'line_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function rows()
    {
        return $this->hasMany(LineBalancingReportRow::class, 'line_balancing_report_id')->orderBy('row_number');
    }

    /**
     * Get the allowance multiplier (e.g., 1.15 for 15% allowance).
     */
    public function getAllowanceMultiplierAttribute(): float
    {
        return 1 + ($this->allowance_percent / 100);
    }

    /**
     * Get total working seconds per day (working_hours * 3600).
     */
    public function getWorkingSecondsPerDayAttribute(): float
    {
        return $this->working_hours_per_day * 3600;
    }
}