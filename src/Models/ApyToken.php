<?php

namespace TomasManuelTM\ApyPayment\Models;

use Illuminate\Database\Eloquent\Model;

class ApyToken extends Model
{
    protected $table = 'apy_tokens';

    protected $fillable = [
        'token',
        'expires_on',
        'expires_in',
        'istoken'
    ];

    protected $casts = [
        'istoken' => 'boolean'
    ];
}