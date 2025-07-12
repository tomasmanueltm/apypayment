<?php

namespace TomasManuelTM\ApyPayment\Models;

use Illuminate\Database\Eloquent\Model;

class ApyMethod extends Model
{
    protected $table = 'apy_methods';

    protected $fillable = [
        'name',
        'method',
        'hash',
        'type',
        'isActive',
        'isDefault',
    ];

    protected $casts = [
        'isActive' => 'boolean',
        'isDefault' => 'boolean'
    ];
}