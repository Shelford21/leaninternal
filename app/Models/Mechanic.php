<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mechanic extends Model
{
    use HasFactory;

    protected $table = 'mechanics';

    protected $fillable = [
        'nik_karyawan',
        'mechanic',
        'description',
        'status',
    ];
}
