<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TxnSchedule extends Model
{
    protected $table = 'txn_schedules';
    protected $primaryKey = 'schedule_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'day',
        'start_time',
        'end_time',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];
}
