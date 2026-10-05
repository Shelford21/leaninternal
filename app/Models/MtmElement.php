<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtmElement extends Model
{
    use HasFactory;

    protected $table = 'mtm_elements';

    protected $fillable = [
        'element_name',
        'description',
        'code',
        'tmu',
        'seconds',
        'status',
    ];

    protected $casts = [
        'tmu' => 'decimal:2',
        'seconds' => 'decimal:2',
    ];
}
