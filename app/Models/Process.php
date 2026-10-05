<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    use HasFactory;

    protected $table = 'processes';

    protected $fillable = [
        'process_name',
        'description',
        'status',
    ];

    public function versions()
    {
        return $this->hasMany(ProcessVersion::class, 'process_id');
    }

    public function latestVersion()
    {
        return $this->hasOne(ProcessVersion::class, 'process_id')->latestOfMany();
    }
}
