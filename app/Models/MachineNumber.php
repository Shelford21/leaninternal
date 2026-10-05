<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineNumber extends Model
{
    use HasFactory;

    protected $table = 'machine_numbers';

    protected $fillable = [
        'machine_number',
        'description',
        'status',
    ];
}
