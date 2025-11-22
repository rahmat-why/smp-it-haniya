<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstEvent extends Model
{
    protected $table = 'mst_events';
    protected $primaryKey = 'event_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'event_name',
        'description',
        'location',
        'status',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];
}
