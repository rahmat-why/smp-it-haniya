<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MstAcademicYear Model
 * 
 * Represents academic years and semesters
 * 
 * @property string $academic_year_id Primary key
 * @property \Carbon\Carbon $start_date Start date of academic year
 * @property \Carbon\Carbon $end_date End date of academic year
 * @property string $semester Semester identifier (e.g., "1", "2")
 * @property string $status Active/Inactive status
 * @property string $created_by User who created the record
 * @property string $updated_by User who last updated the record
 * @property \Carbon\Carbon $created_at Creation timestamp
 * @property \Carbon\Carbon $updated_at Last update timestamp
 */
class MstAcademicYear extends Model
{
    /**
     * Table name (database uses plural)
     */
    protected $table = 'mst_academic_years';

    /**
     * Primary key
     */
    protected $primaryKey = 'academic_year_id';

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
        'academic_year_id',
        'start_date',
        'end_date',
        'semester',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Timestamp columns
     */
    public $timestamps = true;

    /**
     * Cast start_date and end_date to dates
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
