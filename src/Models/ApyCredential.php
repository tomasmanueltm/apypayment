<?php

namespace TomasManuelTM\ApyPayment\Models;

use Illuminate\Database\Eloquent\Model;

class ApyCredential extends Model
{
    protected $table = 'apy_credentials';

    protected $fillable = [
        'token',
        'client_id',
        'client_secret',
        'resource',
        'auth_url',
        'api_url',
        'expires_on',
        'expires_in',
        'istoken'
    ];

    protected $casts = [
        'istoken' => 'boolean'
    ];
}