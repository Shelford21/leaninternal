<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionRole extends Model
{
    use HasFactory;

    protected $table = 'production_roles';

    protected $fillable = [
        'production_role',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
}
