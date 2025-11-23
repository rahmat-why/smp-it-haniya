<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TxnGradeDetail extends Model
{
    use HasFactory;

    protected $table = 'txn_grade_details';
    protected $primaryKey = 'grade_detail_id';
    public $incrementing = false; // because grade_detail_id is string
    protected $keyType = 'string';

    protected $fillable = [
        'grade_detail_id',
        'grade_id',
        'student_id',
        'grade_value',
        'grade_attitude',
        'notes',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;

    // Relationship: Detail belongs to Grade
    public function grade()
    {
        return $this->belongsTo(TxnGrade::class, 'grade_id', 'grade_id');
    }

    // Relationship: Detail belongs to Student
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}