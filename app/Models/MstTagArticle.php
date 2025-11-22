<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstTagArticle extends Model
{
    protected $table = 'mst_tag_articles';
    protected $primaryKey = 'tag_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'tag_id',
        'article_id',
        'tag_code',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];
}
