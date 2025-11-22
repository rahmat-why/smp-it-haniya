<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MstClass Model
 * 
 * Represents classes in the school
 * 
 * @property string $class_id Primary key
 * @property string $class_name Name of the class
 * @property string $class_level Level/grade of the class
 * Homeroom teacher is now stored on mst_academic_classes (per academic year)
 * @property string $created_by User who created the record
 * @property string $updated_by User who last updated the record
 * @property \Carbon\Carbon $created_at Creation timestamp
 * @property \Carbon\Carbon $updated_at Last update timestamp
 */
class MstClass extends Model
{
    /**
     * Table name
     */
    protected $table = 'mst_classes';

    /**
     * Primary key
     */
    protected $primaryKey = 'class_id';

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
        'class_id',
        'class_name',
        'class_level',
        'created_by',
        'updated_by',
    ];

    /**
     * Timestamp columns
     */
    public $timestamps = true;
}
