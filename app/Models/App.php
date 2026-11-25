<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;

class App extends Model
{
    protected $table = 'apps';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'ikon',
        'base_url',
        'redirect_uri',
        'backchannel_logout_uri',
        'client_id',
        'client_secret_hash',
        'hmac_secret_encrypted',
        'ip_allowlist',
        'aktif',
    ];

    protected $hidden = [
        'client_secret_hash',
        'hmac_secret_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'hmac_secret_encrypted' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('sso:active_apps'));
        static::deleted(fn () => Cache::forget('sso:active_apps'));
    }

    public function userLevels(): HasMany
    {
        return $this->hasMany(UserAppLevel::class, 'app_id');
    }

    public function verifySecret(string $plainSecret): bool
    {
        return password_verify($plainSecret, $this->client_secret_hash);
    }

    /**
     * Check whether the caller IP is allowed (supports exact IPs and CIDR ranges).
     */
    public function isIpAllowed(string $ip): bool
    {
        $raw = trim((string) $this->ip_allowlist);
        if ($raw === '') {
            return true;
        }

        $allowed = array_filter(array_map('trim', explode(',', $raw)));

        return IpUtils::checkIp($ip, $allowed);
    }
}
