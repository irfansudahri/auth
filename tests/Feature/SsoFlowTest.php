<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\SsoSession;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sso.siakad.driver' => 'demo']);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_launcher_and_generic_error_on_bad_password(): void
    {
        $bad = $this->post('/login', [
            'username' => 'dosen1',
            'password' => 'wrong-password',
        ]);
        $bad->assertSessionHasErrors('username');

        $ok = $this->post('/login', [
            'username' => 'dosen1',
            'password' => 'Dosen123!',
        ]);
        $ok->assertRedirect('/');

        $launcher = $this->get('/');
        $launcher->assertOk();
        $launcher->assertSee('Sistem OBE');
        $launcher->assertSee('Manajemen Kelas');
        $launcher->assertDontSee('Portal Admin SSO');
    }

    public function test_login_rate_limiting_after_five_failures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'dosen1',
                'password' => 'salah',
            ]);
        }

        $locked = $this->post('/login', [
            'username' => 'dosen1',
            'password' => 'Dosen123!',
        ]);
        $locked->assertSessionHasErrors('username');
        $this->assertDatabaseHas('audit_log', ['aksi' => 'LOGIN_RATE_LIMITED']);
    }

    public function test_authorize_verify_pkce_and_replay_protection(): void
    {
        $this->post('/login', [
            'username' => 'dosen1',
            'password' => 'Dosen123!',
        ])->assertRedirect('/');

        $verifier = bin2hex(random_bytes(32));
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        // Wrong redirect_uri must be rejected
        $this->get('/authorize?'.http_build_query([
            'client_id' => 'app-obe-client',
            'redirect_uri' => 'https://evil.com/callback',
            'state' => 'state123456',
        ]))->assertStatus(400);

        // Valid authorize with PKCE
        $authResponse = $this->get('/authorize?'.http_build_query([
            'client_id' => 'app-obe-client',
            'redirect_uri' => 'http://localhost:8888/kelas/public/index.php/sso/callback',
            'state' => 'state123456',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]));

        $authResponse->assertRedirect();
        $location = $authResponse->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('code', $query);
        $this->assertSame('state123456', $query['state']);

        // Wrong PKCE verifier fails
        $this->postJson('/api/sso/verify', [
            'client_id' => 'app-obe-client',
            'client_secret' => 'obe-secret-dev',
            'code' => $query['code'],
            'redirect_uri' => 'http://localhost:8888/kelas/public/index.php/sso/callback',
            'code_verifier' => 'wrong-verifier',
        ])->assertStatus(401);

        // Correct verify succeeds and returns only OBE levels
        $verify = $this->postJson('/api/sso/verify', [
            'client_id' => 'app-obe-client',
            'client_secret' => 'obe-secret-dev',
            'code' => $query['code'],
            'redirect_uri' => 'http://localhost:8888/kelas/public/index.php/sso/callback',
            'code_verifier' => $verifier,
        ]);

        $verify->assertOk()
            ->assertJson([
                'siakad_user_id' => 2,
                'username' => 'dosen1',
                'levels' => ['dosen', 'kaprodi'],
            ]);

        $ssoSessionId = $verify->json('sso_session_id');

        // Replay of the same code must fail AND revoke the SSO session
        $this->postJson('/api/sso/verify', [
            'client_id' => 'app-obe-client',
            'client_secret' => 'obe-secret-dev',
            'code' => $query['code'],
            'redirect_uri' => 'http://localhost:8888/kelas/public/index.php/sso/callback',
            'code_verifier' => $verifier,
        ])->assertStatus(401);

        $this->assertNotNull(SsoSession::find($ssoSessionId)?->dicabut_pada);
    }

    public function test_introspect_and_backchannel_logout_hmac(): void
    {
        Http::fake();

        $this->post('/login', [
            'username' => 'dosen1',
            'password' => 'Dosen123!',
        ])->assertRedirect('/');

        $ssoSessionId = session('sso_session_id');

        // Register app session
        $this->postJson('/api/sso/register-session', [
            'client_id' => 'app-obe-client',
            'client_secret' => 'obe-secret-dev',
            'sso_session_id' => $ssoSessionId,
            'sid_aplikasi' => 'ci4_sess_abc123',
        ])->assertOk()->assertJson(['registered' => true]);

        // Introspect active
        $this->postJson('/api/sso/introspect', [
            'client_id' => 'app-obe-client',
            'client_secret' => 'obe-secret-dev',
            'sso_session_id' => $ssoSessionId,
        ])->assertOk()->assertJson([
            'aktif' => true,
            'levels' => ['dosen', 'kaprodi'],
        ]);

        // Logout triggers HMAC-signed back-channel POST
        $this->post('/logout')->assertRedirect('/login');

        Http::assertSent(function ($request) {
            $expectedSig = hash_hmac('sha256', $request->body(), 'obe-hmac-secret-dev');

            return $request->url() === 'http://localhost:8888/kelas/public/index.php/sso/backchannel-logout'
                && hash_equals($expectedSig, $request->header('X-Signature')[0] ?? '');
        });

        // Introspect after logout is inactive
        $this->postJson('/api/sso/introspect', [
            'client_id' => 'app-obe-client',
            'client_secret' => 'obe-secret-dev',
            'sso_session_id' => $ssoSessionId,
        ])->assertOk()->assertJson([
            'aktif' => false,
            'levels' => [],
        ]);
    }

    public function test_admin_routes_protected_and_app_registration_works(): void
    {
        // Non-admin cannot access /admin/aplikasi
        $this->post('/login', [
            'username' => 'mhs1',
            'password' => 'Mhs123!',
        ])->assertRedirect('/');

        $this->get('/admin/aplikasi')->assertStatus(403);

        $this->post('/logout');

        // Superadmin can access and create a new app
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'Admin123!',
        ])->assertRedirect('/');

        $this->post('/admin/aplikasi', [
            'kode' => 'simpeg',
            'nama' => 'Sistem Kepegawaian',
            'base_url' => 'https://simpeg.domain.com',
            'redirect_uri' => 'https://simpeg.domain.com/sso/callback',
            'backchannel_logout_uri' => 'https://simpeg.domain.com/sso/backchannel-logout',
        ])->assertRedirect('/admin/aplikasi');

        $this->assertDatabaseHas('apps', ['kode' => 'simpeg', 'aktif' => true]);
        $app = App::where('kode', 'simpeg')->first();
        $this->assertNotNull($app->hmac_secret_encrypted);
    }
}
