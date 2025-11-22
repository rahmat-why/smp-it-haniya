<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TxnGrade extends Model
{
    protected $table = 'txn_grades';
    protected $primaryKey = 'grade_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'grade_id',
        'student_class_id',
        'subject_id',
        'teacher_id',
        'grade_type',
        'grade_value',
        'grade_attitude',
        'notes',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'grade_value' => 'decimal:2',
    ];
}
