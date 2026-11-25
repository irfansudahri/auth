<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAppLevel extends Model
{
    protected $table = 'user_app_level';

    protected $fillable = [
        'tenant_key',
        'siakad_user_id',
        'app_id',
        'level',
        'aktif',
        'berlaku_sampai',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'berlaku_sampai' => 'date',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function scopeActiveAndValid(Builder $query): Builder
    {
        return $query->where('aktif', true)
            ->where(function (Builder $q) {
                $q->whereNull('berlaku_sampai')
                    ->orWhereDate('berlaku_sampai', '>=', now()->toDateString());
            });
    }
}
