<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillGrading extends Model
{
    use HasFactory;

    protected $table = 'skill_gradings';

    protected $fillable = [
        'skill_grade',
        'description',
        'status',
    ];
}
