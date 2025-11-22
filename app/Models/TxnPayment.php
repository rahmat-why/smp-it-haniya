<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TxnPayment extends Model
{
    protected $table = 'txn_payments';
    protected $primaryKey = 'payment_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'student_class_id',
        'payment_type',
        'total_payment',
        'remaining_payment',
        'status',
        'notes',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_payment' => 'decimal:2',
        'remaining_payment' => 'decimal:2',
    ];
}
