<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GsdCategory extends Model
{
    use HasFactory;

    protected $table = 'gsd_categories';

    protected $fillable = [
        'category_name',
        'description',
        'status',
    ];

    public function gsdElements()
    {
        return $this->hasMany(GsdElement::class, 'gsd_category_id');
    }

    public function processVersions()
    {
        return $this->hasMany(ProcessVersion::class, 'gsd_category_id');
    }
}
