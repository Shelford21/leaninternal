<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineBalancingReportRow extends Model
{
    use HasFactory;

    protected $table = 'line_balancing_report_rows';

    protected $fillable = [
        'line_balancing_report_id',
        'row_number',
        'machine_type_id',
        'process',
        'name',
        'employee_id',
        'joint_process',
        'operator',
        'ct_1',
        'ct_2',
        'ct_3',
        'ct_4',
        'ct_5',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'operator' => 'integer',
        'ct_1' => 'decimal:2',
        'ct_2' => 'decimal:2',
        'ct_3' => 'decimal:2',
        'ct_4' => 'decimal:2',
        'ct_5' => 'decimal:2',
    ];

    public function report()
    {
        return $this->belongsTo(LineBalancingReport::class, 'line_balancing_report_id');
    }

    public function machineType()
    {
        return $this->belongsTo(MachineType::class, 'machine_type_id');
    }

    public function employee()
    {
        return $this->belongsTo(Operator::class, 'employee_id');
    }

    /**
     * Get array of non-null cycle time observations.
     */
    public function getCycleTimeObservationsAttribute(): array
    {
        return array_filter([
            $this->ct_1,
            $this->ct_2,
            $this->ct_3,
            $this->ct_4,
            $this->ct_5,
        ], fn($v) => $v !== null && $v > 0);
    }

    // Average Cycle Time = AVERAGE(CT1:CT5) — only non-null values
    public function getAvgCycleTimeAttribute(): float
    {
        $obs = $this->cycle_time_observations;
        return count($obs) > 0 ? array_sum($obs) / count($obs) : 0;
    }

    // Average CT + Allowance (configurable %) = avg_ct * (1 + allowance_percent/100)
    public function getAvgCycleTimeAllowanceAttribute(): float
    {
        $report = $this->report;
        $multiplier = $report ? $report->allowance_multiplier : 1.15;
        return round($this->avg_cycle_time * $multiplier, 2);
    }

    // Average CT / Process = Average Cycle Time (per workbook)
    public function getAvgCtPerProcessAttribute(): float
    {
        return round($this->avg_cycle_time, 2);
    }

    // Output / Hour = 3600 / (Average CT + Allowance)
    public function getOutputPerHourAttribute(): float
    {
        if ($this->avg_cycle_time_allowance <= 0)
            return 0;
        return round(3600 / $this->avg_cycle_time_allowance, 2);
    }

    // Output Process / Hour = Output / Hour (per workbook)
    public function getOutputProcessPerHourAttribute(): float
    {
        return $this->output_per_hour;
    }

    // Request Operator = Average CT / Process / Takt Time
    public function getRequestOperatorAttribute(): float
    {
        $report = $this->report;
        if (!$report || $report->target_output_per_hour <= 0)
            return 0;
        $taktTime = 3600 / $report->target_output_per_hour;
        if ($taktTime <= 0)
            return 0;
        return round($this->avg_ct_per_process / $taktTime, 2);
    }

    // Potential Output / Process = Output Process / Hour * working_hours_per_day
    public function getPotentialOutputPerProcessAttribute(): float
    {
        $report = $this->report;
        $hours = $report ? $report->working_hours_per_day : 8;
        return round($this->output_process_per_hour * $hours, 0);
    }
}