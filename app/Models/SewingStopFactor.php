<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SewingStopFactor extends Model
{
    use HasFactory;

    protected $table = 'sewing_stop_factors';

    protected $fillable = [
        'factor_name',
        'description',
        'tolerance',
        'factor_value',
        'code',
        'status',
    ];

    protected $casts = [
        'factor_value' => 'decimal:2',
    ];
}
