<?php

namespace TomasManuelTM\ApyPayment\Models;

use Illuminate\Database\Eloquent\Model;

class ApyPayment extends Model
{
    protected $table = 'apy_payments';

    protected $fillable = [
        'reference',
        'merchantTransactionId',
        'type',
        'description',
        'status',
        'amount',
        'dueDate',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'dueDate' => 'datetime'
    ];
}