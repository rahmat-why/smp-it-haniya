<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TxnPaymentInstalment extends Model
{
    protected $table = 'txn_payment_instalments';
    protected $primaryKey = 'instalment_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'instalment_id',
        'payment_id',
        'instalment_number',
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

    public function payment()
    {
        return $this->belongsTo(TxnPayment::class, 'payment_id', 'payment_id');
    }
}
