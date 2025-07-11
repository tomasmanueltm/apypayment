<?php

namespace TomasManuelTM\ApyPayment\Models;

use Illuminate\Database\Eloquent\Model;

class ApyPayment extends Model
{
    protected $table = 'apy_payments';
    protected $primaryKey = 'idPayment';

    protected $fillable = [
        'id',
        'merchantTransactionId',
        'type',
        'operation',
        'amount',
        'currency',
        'status',
        'description',
        'disputes',
        'applicationFeeAmount',
        'paymentMethod',
        'options',
        'reference',
        'createdDate',
        'updatedDate'
    ];

    protected $casts = [
        'options' => 'json',
        'reference' => 'json',
        'disputes' => 'boolean',
        'amount' => 'decimal:2',
        'applicationFeeAmount' => 'decimal:2',
        'createdDate' => 'datetime',
        'updatedDate' => 'datetime'
    ];
}