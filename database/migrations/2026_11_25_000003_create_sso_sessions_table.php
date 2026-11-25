<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_sessions', function (Blueprint $table) {
            $table->char('id', 64)->primary();
            $table->unsignedBigInteger('siakad_user_id')->index();
            $table->string('username', 100);
            $table->string('nama', 150);
            $table->string('ip', 45);
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('aktivitas_terakhir')->useCurrent()->index();
            $table->timestamp('dicabut_pada')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_sessions');
    }
};
