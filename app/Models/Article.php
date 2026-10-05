<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $table = 'articles';

    protected $fillable = [
        'article_name',
        'label_number',
        'label_number_quty',
        'destination',
        'description',
        'photo_path',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function ptmsReports()
    {
        return $this->hasMany(PtmsReport::class, 'article_id');
    }

    protected static function booted(): void
    {
        static::saving(function (Article $article) {
            $article->label_number_quty = $article->label_number
                ? $article->label_number . '17596'
                : null;
        });
    }
}
