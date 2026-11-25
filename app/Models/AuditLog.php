<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    public $timestamps = false;

    protected $fillable = [
        'waktu',
        'tenant_key',
        'siakad_user_id',
        'app_id',
        'aksi',
        'detail',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
            'detail' => 'array',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public static function record(
        string $aksi,
        ?int $siakadUserId = null,
        ?int $appId = null,
        array $detail = [],
        ?string $ip = null,
        ?string $tenantKey = null
    ): self {
        return self::create([
            'waktu' => now(),
            'tenant_key' => $tenantKey ?? config('sso.multi_tenant.default_tenant', 'default'),
            'siakad_user_id' => $siakadUserId,
            'app_id' => $appId,
            'aksi' => $aksi,
            'detail' => $detail,
            'ip' => $ip ?? request()?->ip(),
        ]);
    }
}
