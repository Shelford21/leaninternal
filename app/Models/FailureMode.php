<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailureMode extends Model
{
    use HasFactory;

    protected $table = 'failure_modes';

    protected $fillable = [
        'failure_mode',
        'description',
        'status',
    ];
}
