<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TxnAttendance extends Model
{
    // actual table name is plural in the DB
    protected $table = 'txn_attendances';
    protected $primaryKey = 'attendance_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        // header fields (attendance header table)
        'attendance_id',
        'attendance_date',
        'academic_class_id',
        'teacher_id',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];
}
