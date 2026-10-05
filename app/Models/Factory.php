<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factory extends Model
{
    use HasFactory;

    protected $table = 'factories';

    protected $fillable = [
        'factory_name',
        'description',
        'status',
    ];

    public function departments()
    {
        return $this->hasMany(Department::class, 'factory_id');
    }

    public function ptmsReports()
    {
        return $this->hasMany(PtmsReport::class, 'factory_id');
    }
}
