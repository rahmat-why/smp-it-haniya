<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TxnPaymentInstallment extends Model
{
    protected $table = 'txn_payment_installments';
    protected $primaryKey = 'installment_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'installment_id',
        'payment_id',
        'installment_number',
        'total_payment',
        'payment_date',
        'notes',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_payment' => 'decimal:2',
        'payment_date' => 'date',
    ];
}
