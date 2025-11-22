<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstStudentClassDetail extends Model
{
    // This is a helper model for efficient student list retrieval
    // We use raw queries, but this model helps with relationships if needed
    protected $table = 'mst_student_classes';
    protected $primaryKey = 'student_class_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'student_class_id',
        'student_id',
        'class_id',
        'academic_year_id',
        'status',
    ];
}
