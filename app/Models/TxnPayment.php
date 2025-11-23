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
        'payment_date',
        'payment_method',
        'total_payment',
        'remaining_payment',
        'total_price',
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
        'total_price' => 'decimal:2',
    ];

    public function instalments()
    {
        return $this->hasMany(TxnPaymentInstalment::class, 'payment_id', 'payment_id')
                    ->orderBy('instalment_number', 'ASC');
    }

}
