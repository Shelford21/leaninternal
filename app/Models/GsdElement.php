<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GsdElement extends Model
{
    use HasFactory;

    protected $table = 'gsd_elements';

    protected $fillable = [
        'gsd_category_id',
        'element_name',
        'description',
        'code',
        'tmu',
        'seconds',
        'motion_sequence',
        'status',
    ];

    protected $casts = [
        'tmu' => 'decimal:2',
        'seconds' => 'decimal:2',
    ];

    public function gsdCategory()
    {
        return $this->belongsTo(GsdCategory::class, 'gsd_category_id');
    }

    public function processVersions()
    {
        return $this->hasMany(ProcessVersion::class, 'gsd_element_id');
    }

    public function linkedProcessVersions()
    {
        return $this->belongsToMany(
            ProcessVersion::class,
            'process_version_gsd_elements',
            'gsd_element_id',
            'process_version_id'
        )->withTimestamps();
    }
}
