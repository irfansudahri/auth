<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SsoCode extends Model
{
    protected $table = 'sso_codes';

    protected $primaryKey = 'code_hash';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code_hash',
        'tenant_key',
        'siakad_user_id',
        'app_id',
        'sso_session_id',
        'redirect_uri',
        'state',
        'code_challenge',
        'code_challenge_method',
        'expired_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function ssoSession(): BelongsTo
    {
        return $this->belongsTo(SsoSession::class, 'sso_session_id', 'id');
    }
}
