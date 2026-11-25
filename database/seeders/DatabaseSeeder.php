<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\UserAppLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Demo Siakad users (used when SIAKAD_AUTH_DRIVER=demo)
        $demoUsers = [
            ['id' => 1, 'username' => 'admin', 'password' => Hash::make('Admin123!'), 'nama' => 'Administrator SSO', 'email' => 'admin@domain.com', 'aktif' => true],
            ['id' => 2, 'username' => 'dosen1', 'password' => Hash::make('Dosen123!'), 'nama' => 'Dr. Budi Santoso', 'email' => 'budi@domain.com', 'aktif' => true],
            ['id' => 3, 'username' => 'mhs1', 'password' => Hash::make('Mhs123!'), 'nama' => 'Siti Aminah', 'email' => 'siti@domain.com', 'aktif' => true],
        ];

        foreach ($demoUsers as $user) {
            DB::table('demo_siakad_users')->updateOrInsert(
                ['username' => $user['username']],
                array_merge($user, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // 2. Registered SSO Client Applications
        $authApp = App::updateOrCreate(
            ['kode' => 'auth'],
            [
                'nama' => 'Portal Admin SSO',
                'deskripsi' => 'Manajemen kredensial aplikasi klien, hak akses, dan audit log',
                'ikon' => 'shield',
                'base_url' => config('app.url'),
                'redirect_uri' => rtrim((string) config('app.url'), '/').'/admin/akses',
                'backchannel_logout_uri' => null,
                'client_id' => 'sso-auth-portal',
                'client_secret_hash' => password_hash('internal-portal-secret', PASSWORD_BCRYPT),
                'hmac_secret_encrypted' => bin2hex(random_bytes(32)),
                'aktif' => true,
            ]
        );

        $clientDefs = [
            ['kode' => 'obe', 'nama' => 'Sistem OBE', 'deskripsi' => 'Outcome-Based Education & Kurikulum', 'ikon' => 'academic-cap', 'base_url' => 'http://localhost:8888/kelas/public/index.php'],
            ['kode' => 'kelas', 'nama' => 'Manajemen Kelas (OBE)', 'deskripsi' => 'Perkuliahan, presensi, dan nilai kelas', 'ikon' => 'book-open', 'base_url' => 'http://localhost:8888/kelas/public/index.php'],
            ['kode' => 'skp', 'nama' => 'Sistem SKP', 'deskripsi' => 'Sasaran Kinerja Pegawai & Dosen', 'ikon' => 'clipboard-check'],
            ['kode' => 'skpi', 'nama' => 'Sistem SKPI', 'deskripsi' => 'Surat Keterangan Pendamping Ijazah', 'ikon' => 'document-text'],
            ['kode' => 'alumni', 'nama' => 'Portal Alumni', 'deskripsi' => 'Tracer study & jejaring alumni', 'ikon' => 'briefcase'],
            ['kode' => 'akreditasi', 'nama' => 'Akreditasi', 'deskripsi' => 'Borangan, LED, dan penjaminan mutu', 'ikon' => 'chart-bar'],
            ['kode' => 'simpati', 'nama' => 'SIMPATI', 'deskripsi' => 'Sistem Informasi Terpadu Kepegawaian & Layanan', 'ikon' => 'users'],
            ['kode' => 'siam', 'nama' => 'SIAM', 'deskripsi' => 'Sistem Informasi Akademik Mahasiswa', 'ikon' => 'academic-cap'],
        ];

        $seededApps = [];
        foreach ($clientDefs as $def) {
            $kode = $def['kode'];
            $baseUrl = rtrim($def['base_url'] ?? "http://localhost:8888/{$kode}", '/');
            $seededApps[$kode] = App::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $def['nama'],
                    'deskripsi' => $def['deskripsi'],
                    'ikon' => $def['ikon'],
                    'base_url' => $baseUrl,
                    'redirect_uri' => "{$baseUrl}/sso/callback",
                    'backchannel_logout_uri' => "{$baseUrl}/sso/backchannel-logout",
                    'client_id' => "app-{$kode}-client",
                    'client_secret_hash' => password_hash("{$kode}-secret-dev", PASSWORD_BCRYPT),
                    'hmac_secret_encrypted' => "{$kode}-hmac-secret-dev",
                    'aktif' => true,
                ]
            );
        }

        // 3. User App Levels
        $levels = [
            // Superadmin has access to Portal Admin + all client apps
            ['siakad_user_id' => 1, 'app_id' => $authApp->id, 'level' => 'superadmin'],
            ['siakad_user_id' => 1, 'app_id' => $seededApps['obe']->id, 'level' => 'admin'],
            ['siakad_user_id' => 1, 'app_id' => $seededApps['kelas']->id, 'level' => 'admin'],
            ['siakad_user_id' => 1, 'app_id' => $seededApps['skp']->id, 'level' => 'admin'],
            ['siakad_user_id' => 1, 'app_id' => $seededApps['skpi']->id, 'level' => 'admin'],
            ['siakad_user_id' => 1, 'app_id' => $seededApps['alumni']->id, 'level' => 'admin'],
            ['siakad_user_id' => 1, 'app_id' => $seededApps['akreditasi']->id, 'level' => 'admin'],
            ['siakad_user_id' => 1, 'app_id' => $seededApps['simpati']->id, 'level' => 'admin'],
            ['siakad_user_id' => 1, 'app_id' => $seededApps['siam']->id, 'level' => 'admin'],

            // Dosen demo user
            ['siakad_user_id' => 2, 'app_id' => $seededApps['obe']->id, 'level' => 'dosen'],
            ['siakad_user_id' => 2, 'app_id' => $seededApps['obe']->id, 'level' => 'kaprodi'],
            ['siakad_user_id' => 2, 'app_id' => $seededApps['kelas']->id, 'level' => 'dosen'],
            ['siakad_user_id' => 2, 'app_id' => $seededApps['skp']->id, 'level' => 'dosen'],
            ['siakad_user_id' => 2, 'app_id' => $seededApps['akreditasi']->id, 'level' => 'tim_borang'],
            ['siakad_user_id' => 2, 'app_id' => $seededApps['simpati']->id, 'level' => 'pegawai'],

            // Mahasiswa demo user
            ['siakad_user_id' => 3, 'app_id' => $seededApps['kelas']->id, 'level' => 'mahasiswa'],
            ['siakad_user_id' => 3, 'app_id' => $seededApps['siam']->id, 'level' => 'mahasiswa'],
            ['siakad_user_id' => 3, 'app_id' => $seededApps['skpi']->id, 'level' => 'mahasiswa'],
            ['siakad_user_id' => 3, 'app_id' => $seededApps['alumni']->id, 'level' => 'alumni'],
        ];

        foreach ($levels as $item) {
            UserAppLevel::updateOrCreate(
                [
                    'siakad_user_id' => $item['siakad_user_id'],
                    'app_id' => $item['app_id'],
                    'level' => $item['level'],
                ],
                [
                    'aktif' => true,
                    'dibuat_oleh' => 1,
                ]
            );
        }
    }
}
