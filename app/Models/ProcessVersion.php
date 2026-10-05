<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessVersion extends Model
{
    use HasFactory;

    protected $table = 'process_versions';

    protected $fillable = [
        'process_id',
        'version_number',
        'notes',
        'status',
        'created_by',
        'gsd_category_id',
        'gsd_element_id',
    ];

    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ptmsReports()
    {
        return $this->hasMany(PtmsReport::class, 'process_version_id');
    }

    public function gsdCategory()
    {
        return $this->belongsTo(GsdCategory::class, 'gsd_category_id');
    }

    public function gsdElement()
    {
        return $this->belongsTo(GsdElement::class, 'gsd_element_id');
    }

    public function gsdElements()
    {
        return $this->belongsToMany(
            GsdElement::class,
            'process_version_gsd_elements',
            'process_version_id',
            'gsd_element_id'
        )->withTimestamps();
    }
}
