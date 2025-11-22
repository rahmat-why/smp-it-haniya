<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MstAcademicClass Model
 * 
 * Represents a class in a specific academic year with an assigned homeroom teacher
 * 
 * @property string $academic_class_id Primary key
 * @property string $academic_year_id Foreign key to mst_academic_year
 * @property string $class_id Foreign key to mst_classes
 * @property string $homeroom_teacher_id Foreign key to mst_employees
 */
class MstAcademicClass extends Model
{
    /**
     * Table name
     */
    protected $table = 'mst_academic_classes';

    /**
     * Primary key
     */
    protected $primaryKey = 'academic_class_id';

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
        'academic_class_id',
        'academic_year_id',
        'class_id',
        'homeroom_teacher_id',
    ];

    /**
     * Timestamps are not used in this table
     */
    public $timestamps = false;

    /**
     * Relationships
     */
    public function academicYear()
    {
        return $this->belongsTo(MstAcademicYear::class, 'academic_year_id', 'academic_year_id');
    }

    public function class()
    {
        return $this->belongsTo(MstClass::class, 'class_id', 'class_id');
    }

    public function homeroomTeacher()
    {
        return $this->belongsTo(MstEmployee::class, 'homeroom_teacher_id', 'employee_id');
    }

    public function studentClasses()
    {
        return $this->hasMany(MstStudentClass::class, 'academic_class_id', 'academic_class_id');
    }
}
