<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSO Portal Configuration
    |--------------------------------------------------------------------------
    */
    'code_ttl' => (int) env('SSO_CODE_TTL', 60),
    'session_idle_ttl' => (int) env('SSO_SESSION_IDLE', 1800),
    'session_max_ttl' => (int) env('SSO_SESSION_MAX', 28800),

    'login' => [
        'max_attempt_user' => (int) env('LOGIN_MAX_ATTEMPT_USER', 5),
        'max_attempt_ip' => (int) env('LOGIN_MAX_ATTEMPT_IP', 20),
        'lock_minutes' => (int) env('LOGIN_LOCK_MINUTES', 15),
    ],

    'backchannel' => [
        'timeout_seconds' => (int) env('SSO_BACKCHANNEL_TIMEOUT', 3),
        'max_skew_seconds' => 60,
    ],

    'siakad' => [
        'driver' => env('SIAKAD_AUTH_DRIVER', 'database'), // 'database' or 'demo'
        'connection' => env('SIAKAD_DB_CONNECTION', 'siakad'),
        'table' => env('SIAKAD_USER_TABLE', 'login_siakad'),
        'columns' => [
            'id' => env('SIAKAD_COL_ID', 'id'),
            'username' => env('SIAKAD_COL_USERNAME', 'username'),
            'password' => env('SIAKAD_COL_PASSWORD', 'password'),
            'name' => env('SIAKAD_COL_NAME', 'nama'),
            'email' => env('SIAKAD_COL_EMAIL', 'email'),
            'active' => env('SIAKAD_COL_ACTIVE', 'aktif'),
        ],
        // Supported: 'auto' (bcrypt/argon2 + md5/sha1 fallback), 'bcrypt', 'md5', 'sha1'
        'hash_algorithm' => env('SIAKAD_HASH_ALGO', 'auto'),
    ],

    'admin_app_code' => env('SSO_ADMIN_APP_CODE', 'auth'),
    'admin_levels' => ['superadmin', 'admin'],

    'multi_tenant' => [
        'enabled' => (bool) env('SSO_MULTI_TENANT', false),
        'connection' => env('MASTER_KAMPUS_DB_CONNECTION', 'master_kampus'),
        'table' => env('MASTER_KAMPUS_TENANT_TABLE', 'tenant'),
        'default_tenant' => env('SSO_DEFAULT_TENANT', 'default'),
    ],
];
