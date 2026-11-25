<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SiakadAuthService
{
    private const DUMMY_BCRYPT = '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012345';

    public function findByUsername(string $username, ?string $tenantDb = null): ?object
    {
        if (config('sso.siakad.driver') === 'synced') {
            $query = DB::table('siakad_users')
                ->selectRaw("id, tenant_key, userid as username, password, nama_lengkap as nama, NULL as email, (blokir = 'N') as aktif")
                ->where('userid', $username);

            if ($tenantDb && $tenantDb !== 'default') {
                $query->where('tenant_key', $tenantDb);
            }

            return $query->first() ?: null;
        }

        $cols = config('sso.siakad.columns');

        $row = $this->baseQuery($tenantDb)
            ->select([
                "{$cols['id']} as id",
                "{$cols['username']} as username",
                "{$cols['password']} as password",
                "{$cols['name']} as nama",
                "{$cols['email']} as email",
                "{$cols['active']} as aktif",
            ])
            ->where($cols['username'], $username)
            ->first();

        return $row ?: null;
    }

    public function findById(int $id, ?string $tenantDb = null): ?object
    {
        if (config('sso.siakad.driver') === 'synced') {
            return DB::table('siakad_users')
                ->selectRaw("id, tenant_key, userid as username, nama_lengkap as nama, NULL as email, (blokir = 'N') as aktif")
                ->where('id', $id)
                ->first() ?: null;
        }

        $cols = config('sso.siakad.columns');

        $row = $this->baseQuery($tenantDb)
            ->select([
                "{$cols['id']} as id",
                "{$cols['username']} as username",
                "{$cols['name']} as nama",
                "{$cols['email']} as email",
                "{$cols['active']} as aktif",
            ])
            ->where($cols['id'], $id)
            ->first();

        return $row ?: null;
    }

    public function searchUsers(string $query = '', int $limit = 25, ?string $tenantDb = null): Collection
    {
        if (config('sso.siakad.driver') === 'synced') {
            $builder = DB::table('siakad_users')
                ->selectRaw("id, tenant_key, userid as username, nama_lengkap as nama, NULL as email, (blokir = 'N') as aktif");

            if ($tenantDb && $tenantDb !== 'default') {
                $builder->where('tenant_key', $tenantDb);
            }

            if ($query !== '') {
                $builder->where(function ($q) use ($query) {
                    $q->where('userid', 'like', "%{$query}%")
                        ->orWhere('nama_lengkap', 'like', "%{$query}%");
                });
            }

            return $builder->orderBy('nama_lengkap')->limit($limit)->get();
        }

        $cols = config('sso.siakad.columns');

        $builder = $this->baseQuery($tenantDb)
            ->select([
                "{$cols['id']} as id",
                "{$cols['username']} as username",
                "{$cols['name']} as nama",
                "{$cols['email']} as email",
                "{$cols['active']} as aktif",
            ]);

        if ($query !== '') {
            $builder->where(function ($q) use ($cols, $query) {
                $q->where($cols['username'], 'like', "%{$query}%")
                    ->orWhere($cols['name'], 'like', "%{$query}%");
            });
        }

        return $builder->orderBy($cols['name'])->limit($limit)->get();
    }

    /**
     * Timing-safe password verification supporting bcrypt/argon2 and legacy MD5/SHA1.
     */
    public function verifyPassword(string $plainPassword, ?string $storedHash): bool
    {
        if ($storedHash === null || $storedHash === '') {
            password_verify($plainPassword, self::DUMMY_BCRYPT);

            return false;
        }

        $algo = config('sso.siakad.hash_algorithm', 'auto');

        if ($algo === 'md5' || ($algo === 'auto' && preg_match('/^[a-f0-9]{32}$/i', $storedHash))) {
            return hash_equals(strtolower($storedHash), md5($plainPassword));
        }

        if ($algo === 'sha1' || ($algo === 'auto' && preg_match('/^[a-f0-9]{40}$/i', $storedHash))) {
            return hash_equals(strtolower($storedHash), sha1($plainPassword));
        }

        return password_verify($plainPassword, $storedHash);
    }

    private function baseQuery(?string $tenantDb = null)
    {
        if (config('sso.siakad.driver') === 'demo') {
            return DB::table('demo_siakad_users');
        }

        $connName = config('sso.siakad.connection');
        if ($tenantDb && $tenantDb !== 'default') {
            config(["database.connections.{$connName}.database" => $tenantDb]);
            DB::purge($connName);
        }

        return DB::connection($connName)
            ->table(config('sso.siakad.table'));
    }
}
