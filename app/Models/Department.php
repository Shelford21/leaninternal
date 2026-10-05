<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    protected $fillable = [
        'factory_id',
        'department_name',
        'desription', // NOTE: typo exists in migration — keeping consistent
        'status',
    ];

    public function factory()
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    public function ptmsReports()
    {
        return $this->hasMany(PtmsReport::class, 'department_id');
    }
}
