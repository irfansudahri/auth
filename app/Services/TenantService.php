<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class TenantService
{
    public function isEnabled(): bool
    {
        return (bool) config('sso.multi_tenant.enabled', false);
    }

    /**
     * Return distinct active campuses (grouped by siakad_name) from master_kampus.tenant.
     *
     * @return Collection<int, object{tenant_key: string, label: string, apps: array<string, string>}>
     */
    public function listCampuses(): Collection
    {
        if (! $this->isEnabled()) {
            return collect();
        }

        return Cache::remember('sso:master_kampus:campuses', 60, function () {
            try {
                $rows = DB::connection(config('sso.multi_tenant.connection', 'master_kampus'))
                    ->table(config('sso.multi_tenant.table', 'tenant'))
                    ->select(['siakad_name', 'nama_kampus', 'app', 'host'])
                    ->where('is_active', 1)
                    ->where('siakad_name', '!=', '')
                    ->orderBy('nama_kampus')
                    ->get();

                return $rows->groupBy('siakad_name')->map(function (Collection $group, string $siakadName) {
                    $apps = [];
                    foreach ($group as $row) {
                        $apps[strtolower((string) $row->app)] = (string) $row->host;
                    }

                    $firstLabel = $group->pluck('nama_kampus')->filter()->first() ?: $siakadName;

                    return (object) [
                        'tenant_key' => $siakadName,
                        'label' => $firstLabel,
                        'apps' => $apps,
                    ];
                })->values();
            } catch (Throwable) {
                return collect();
            }
        });
    }

    public function findCampus(string $tenantKey): ?object
    {
        return $this->listCampuses()->firstWhere('tenant_key', $tenantKey);
    }

    public function resolveBaseUrl(string $tenantKey, string $appKode, string $fallbackBaseUrl): string
    {
        $campus = $this->findCampus($tenantKey);
        if (! $campus) {
            return $fallbackBaseUrl;
        }

        $host = $campus->apps[strtolower($appKode)] ?? null;
        if (! $host || $host === 'localhost') {
            return $fallbackBaseUrl;
        }

        return str_starts_with($host, 'http') ? rtrim($host, '/') : 'https://'.rtrim($host, '/');
    }
}
