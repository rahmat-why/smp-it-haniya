<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MstStudentClass Model
 * 
 * Represents the assignment of a student to an academic class
 * 
 * @property string $student_class_id Primary key
 * @property string $student_id Foreign key to mst_students
 * @property string $academic_class_id Foreign key to mst_academic_classes
 * @property string $created_by User who created the record
 * @property string $updated_by User who last updated the record
 * @property \Carbon\Carbon $created_at Creation timestamp
 * @property \Carbon\Carbon $updated_at Last update timestamp
 */
class MstStudentClass extends Model
{
    /**
     * Table name
     */
    protected $table = 'mst_student_classes';

    /**
     * Primary key
     */
    protected $primaryKey = 'student_class_id';

    /**
     * Primary key is not auto-incrementing
     */
    public $incrementing = false;

    /**
     * Primary key type
     */
    protected $keyType = 'string';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'student_class_id',
        'student_id',
        'academic_class_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Timestamp columns
     */
    public $timestamps = true;

    /**
     * Relationships
     */
    public function student()
    {
        return $this->belongsTo(MstStudent::class, 'student_id', 'student_id');
    }

    public function academicClass()
    {
        return $this->belongsTo(MstAcademicClass::class, 'academic_class_id', 'academic_class_id');
    }
}
