<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_app_level', function (Blueprint $table) {
            $table->string('tenant_key', 100)->default('default')->after('id');
            $table->dropUnique('ual_user_app_level_unique');
            $table->dropIndex('ual_user_active_idx');
            $table->unique(['tenant_key', 'siakad_user_id', 'app_id', 'level'], 'ual_tenant_user_app_level_unique');
            $table->index(['tenant_key', 'siakad_user_id', 'aktif', 'berlaku_sampai'], 'ual_tenant_user_active_idx');
        });

        Schema::table('sso_sessions', function (Blueprint $table) {
            $table->string('tenant_key', 100)->default('default')->after('id')->index();
        });

        Schema::table('sso_codes', function (Blueprint $table) {
            $table->string('tenant_key', 100)->default('default')->after('code_hash');
        });

        Schema::table('login_attempts', function (Blueprint $table) {
            $table->string('tenant_key', 100)->default('default')->after('id');
            $table->index(['tenant_key', 'username', 'waktu'], 'la_tenant_user_time_idx');
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->string('tenant_key', 100)->default('default')->after('waktu');
            $table->index(['tenant_key', 'waktu'], 'al_tenant_time_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropIndex('al_tenant_time_idx');
            $table->dropColumn('tenant_key');
        });

        Schema::table('login_attempts', function (Blueprint $table) {
            $table->dropIndex('la_tenant_user_time_idx');
            $table->dropColumn('tenant_key');
        });

        Schema::table('sso_codes', function (Blueprint $table) {
            $table->dropColumn('tenant_key');
        });

        Schema::table('sso_sessions', function (Blueprint $table) {
            $table->dropIndex(['tenant_key']);
            $table->dropColumn('tenant_key');
        });

        Schema::table('user_app_level', function (Blueprint $table) {
            $table->dropUnique('ual_tenant_user_app_level_unique');
            $table->dropIndex('ual_tenant_user_active_idx');
            $table->dropColumn('tenant_key');
            $table->unique(['siakad_user_id', 'app_id', 'level'], 'ual_user_app_level_unique');
            $table->index(['siakad_user_id', 'aktif', 'berlaku_sampai'], 'ual_user_active_idx');
        });
    }
};
