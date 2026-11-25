<?php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use App\Models\SsoCode;
use App\Models\SsoSession;
use Illuminate\Console\Command;

class PruneSsoData extends Command
{
    protected $signature = 'sso:prune';

    protected $description = 'Bersihkan kode SSO kedaluwarsa, percobaan login lama, dan sesi SSO yang sudah berakhir';

    public function handle(): int
    {
        $deletedCodes = SsoCode::where('expired_at', '<', now()->subDay())->delete();
        $deletedAttempts = LoginAttempt::where('waktu', '<', now()->subDays(30))->delete();
        $deletedSessions = SsoSession::where(function ($q) {
            $q->whereNotNull('dicabut_pada')
                ->where('dicabut_pada', '<', now()->subDays(7));
        })->orWhere('dibuat_pada', '<', now()->subDays(7))->delete();

        $this->info("Pruned: {$deletedCodes} codes, {$deletedAttempts} login attempts, {$deletedSessions} sessions.");

        return self::SUCCESS;
    }
}
