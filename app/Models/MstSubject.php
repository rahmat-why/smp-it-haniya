<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MstSubject Model
 * 
 * Represents subjects/courses offered in the school
 * 
 * @property string $subject_id Primary key
 * @property string $subject_name Name of the subject
 * @property string $subject_code Subject code
 * @property string $class_level Target class level
 * @property string $description Subject description
 * @property string $created_by User who created the record
 * @property string $updated_by User who last updated the record
 * @property \Carbon\Carbon $created_at Creation timestamp
 * @property \Carbon\Carbon $updated_at Last update timestamp
 */
class MstSubject extends Model
{
    /**
     * Table name
     */
    protected $table = 'mst_subjects';

    /**
     * Primary key
     */
    protected $primaryKey = 'subject_id';

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
        'subject_id',
        'subject_name',
        'subject_code',
        'class_level',
        'description',
        'created_by',
        'updated_by',
    ];

    /**
     * Timestamp columns
     */
    public $timestamps = true;
}
