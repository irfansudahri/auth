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
        ?string $ip = null
    ): self {
        return self::create([
            'waktu' => now(),
            'siakad_user_id' => $siakadUserId,
            'app_id' => $appId,
            'aksi' => $aksi,
            'detail' => $detail,
            'ip' => $ip ?? request()?->ip(),
        ]);
    }
}
