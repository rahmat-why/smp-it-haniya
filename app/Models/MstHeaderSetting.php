<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstHeaderSetting extends Model
{
    protected $table = 'mst_header_settings';
    protected $primaryKey = 'header_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'header_id',
        'title',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];
}
