<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * MstStudent Model
 * 
 * Represents a student in the system.
 * Used for student authentication and management.
 */
class MstStudent extends Authenticatable
{
    protected $table = 'mst_students';
    protected $primaryKey = 'student_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'student_id',
        'first_name',
        'last_name',
        'nis',
        'birth_date',
        'birth_place',
        'gender',
        'address',
        'father_name',
        'mother_name',
        'father_phone',
        'mother_phone',
        'father_job',
        'mother_job',
        'password',
        'entry_date',
        'graduation_date',
        'profile_photo',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $hidden = ['password'];

    /**
     * Get the full name of the student
     * 
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
