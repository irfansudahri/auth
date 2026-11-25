<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    public $timestamps = false;

    protected $fillable = [
        'tenant_key',
        'username',
        'ip',
        'berhasil',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'berhasil' => 'boolean',
            'waktu' => 'datetime',
        ];
    }
}
