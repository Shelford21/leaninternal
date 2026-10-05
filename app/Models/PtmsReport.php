<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PtmsReport extends Model
{
    use HasFactory;

    protected $table = 'ptms_reports';

    protected $fillable = [
        'report_number',
        'article_id',
        'process_version_id',
        'operator_id',
        'factory_id',
        'department_id',
        'line_id',
        'created_by',
        'machine_name',
        'feed_type',
        'rpm',
        'stitch_per_cm',
        'seam_width',
        'machine_delay_percent',
        'contingency_percent',
        'ra_percent',
        'machining_tmu',
        'handling_tmu',
        'bundle_tmu',
        'total_tmu',
        'bms',
        'smv',
        'status',
    ];

    protected $casts = [
        'rpm' => 'decimal:2',
        'stitch_per_cm' => 'decimal:2',
        'seam_width' => 'decimal:2',
        'machine_delay_percent' => 'decimal:2',
        'contingency_percent' => 'decimal:2',
        'ra_percent' => 'decimal:2',
        'machining_tmu' => 'decimal:2',
        'handling_tmu' => 'decimal:2',
        'bundle_tmu' => 'decimal:2',
        'total_tmu' => 'decimal:2',
        'bms' => 'decimal:2',
        'smv' => 'decimal:2',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function processVersion()
    {
        return $this->belongsTo(ProcessVersion::class, 'process_version_id');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function factory()
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'line_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->report_number)) {
                $year = date('Y');
                $last = self::whereYear('created_at', $year)->count() + 1;
                $report->report_number = sprintf('PTMS-%s-%04d', $year, $last);
            }
        });
    }
}
