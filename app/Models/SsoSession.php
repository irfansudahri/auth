<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SsoSession extends Model
{
    protected $table = 'sso_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'siakad_user_id',
        'username',
        'nama',
        'ip',
        'user_agent',
        'dibuat_pada',
        'aktivitas_terakhir',
        'dicabut_pada',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
            'aktivitas_terakhir' => 'datetime',
            'dicabut_pada' => 'datetime',
        ];
    }

    public function appSessions(): HasMany
    {
        return $this->hasMany(AppSession::class, 'sso_session_id', 'id');
    }

    public function isValid(): bool
    {
        if ($this->dicabut_pada !== null) {
            return false;
        }

        $now = now();
        $idleLimit = config('sso.session_idle_ttl', 1800);
        $maxLimit = config('sso.session_max_ttl', 28800);

        if ($this->aktivitas_terakhir->diffInSeconds($now) > $idleLimit) {
            return false;
        }

        if ($this->dibuat_pada->diffInSeconds($now) > $maxLimit) {
            return false;
        }

        return true;
    }

    public function revoke(): void
    {
        $this->update(['dicabut_pada' => now()]);
    }
}
