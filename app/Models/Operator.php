<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $table = 'operators';

    protected $fillable = [
        'employee_number',
        'nik_karyawan',
        'operator_name',
        'photo_path',
        'gender',
        'role',
        'factory_id',
        'department_id',
        'division_id',
        'section_id',
        'line_id',
        'start_date',
        'date_of_birth',
        'status',
        'status_pkwtt_id',
        'educational_level_id',
    ];

    protected $casts = [
        'status' => 'string',
        'start_date' => 'date',
        'date_of_birth' => 'date',
    ];

    /**
     * Working Age: duration from Date of Birth to Start Date.
     * Display: {years} Yr {months} Mth {days} Day
     */
    public function getWorkingAgeAttribute(): ?string
    {
        if (!$this->start_date || !$this->date_of_birth) {
            return null;
        }
        $start = $this->start_date instanceof \Carbon\Carbon ? $this->start_date : \Carbon\Carbon::parse($this->start_date);
        $dob = $this->date_of_birth instanceof \Carbon\Carbon ? $this->date_of_birth : \Carbon\Carbon::parse($this->date_of_birth);
        if ($start->lt($dob)) {
            return null;
        }
        $diff = $dob->diff($start);
        return $diff->y . ' Yr ' . $diff->m . ' Mth ' . $diff->d . ' Day';
    }

    /**
     * Age: duration from Date of Birth to today.
     * Display: {years} Yr {months} Mth {days} Day
     */
    public function getAgeAttribute(): ?string
    {
        if (!$this->date_of_birth) {
            return null;
        }
        $dob = $this->date_of_birth instanceof \Carbon\Carbon ? $this->date_of_birth : \Carbon\Carbon::parse($this->date_of_birth);
        $diff = $dob->diff(now());
        return $diff->y . ' Yr ' . $diff->m . ' Mth ' . $diff->d . ' Day';
    }

    /**
     * Age (year): completed years from Date of Birth to today.
     */
    public function getAgeYearAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        $dob = $this->date_of_birth instanceof \Carbon\Carbon ? $this->date_of_birth : \Carbon\Carbon::parse($this->date_of_birth);
        return $dob->diff(now())->y;
    }

    /**
     * Years of service: remaining time until retirement.
     * Retirement limit = Date of Birth + 59 years 0 months 20 days.
     * Years of Service = Retirement Date − Today.
     * Display: {years} Yr {months} Mth {days} Day
     * Returns null if date_of_birth is missing.
     */
    public function getYearsOfServiceAttribute(): ?string
    {
        if (!$this->date_of_birth) {
            return null;
        }
        $dob = $this->date_of_birth instanceof \Carbon\Carbon ? $this->date_of_birth : \Carbon\Carbon::parse($this->date_of_birth);
        $retirementDate = $dob->copy()->addYears(59)->addDays(20);
        $today = now()->startOfDay();
        if ($today->gte($retirementDate)) {
            return '0 Yr 0 Mth 0 Day';
        }
        $diff = $today->diff($retirementDate);
        return $diff->y . ' Yr ' . $diff->m . ' Mth ' . $diff->d . ' Day';
    }

    public function ptmsReports()
    {
        return $this->hasMany(PtmsReport::class, 'operator_id');
    }

    public function latestPtmsReport()
    {
        return $this->hasOne(PtmsReport::class, 'operator_id')->latestOfMany();
    }

    public function factory()
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'line_id');
    }

    public function statusPkwtt()
    {
        return $this->belongsTo(StatusPkwtt::class, 'status_pkwtt_id');
    }

    public function educationalLevel()
    {
        return $this->belongsTo(EducationalLevel::class, 'educational_level_id');
    }
}
