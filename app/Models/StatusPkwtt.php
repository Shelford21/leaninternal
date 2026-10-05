<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusPkwtt extends Model
{
    use HasFactory;

    protected $table = 'status_pkwtt';

    protected $fillable = [
        'pkwtt',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
}