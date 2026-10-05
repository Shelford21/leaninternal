<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalLevel extends Model
{
    use HasFactory;

    protected $table = 'educational_levels';

    protected $fillable = [
        'level',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
}