<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentsPanel extends Model
{
    use HasFactory;

    protected $table = 'components_panels';

    protected $fillable = [
        'component_panel',
        'description',
        'status',
    ];
}
