<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $table = 'divisions';

    protected $fillable = [
        'division',
        'description',
        'status',
    ];

    public function productionLines()
    {
        return $this->hasMany(ProductionLine::class, 'division_id');
    }
}
