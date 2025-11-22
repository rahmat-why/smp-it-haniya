<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * MstTeacher Model
 * 
 * Represents a teacher in the system.
 * Used for teacher authentication and management.
 */
class MstTeacher extends Authenticatable
{
    protected $table = 'mst_teachers';
    protected $primaryKey = 'teacher_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'teacher_id',
        'first_name',
        'last_name',
        'npk',
        'gender',
        'birth_place',
        'birth_date',
        'profile_photo',
        'address',
        'phone',
        'entry_date',
        'password',
        'level',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $hidden = ['password'];

    /**
     * Get the full name of the teacher
     * 
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
