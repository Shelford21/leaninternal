<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
    use HasFactory;

    protected $table = 'production_lines';

    protected $fillable = [
        'division_id',
        'line_name',
        'description',
        'status',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    /**
     * @deprecated Use division() instead
     */
    public function department()
    {
        return $this->division();
    }

    public function ptmsReports()
    {
        return $this->hasMany(PtmsReport::class, 'line_id');
    }
}
