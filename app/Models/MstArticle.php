<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstArticle extends Model
{
    protected $table = 'mst_articles';
    protected $primaryKey = 'article_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'title',
        'slug',
        'content',
        'image',
        // 'article_type' removed: column no longer used
        'status',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];
}
