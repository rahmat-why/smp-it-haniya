<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * MstEmployee Model
 * 
 * Represents an employee (admin/staff) in the system.
 * Used for admin authentication and management.
 */
class MstEmployee extends Authenticatable
{
    protected $table = 'mst_employees';
    protected $primaryKey = 'employee_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'username',
        'password',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
        'entry_date',
        'level',
        'status',
        'profile_photo',
        'created_by',
        'updated_by'
    ];

    protected $hidden = ['password'];

    /**
     * Get the full name of the employee
     * 
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
