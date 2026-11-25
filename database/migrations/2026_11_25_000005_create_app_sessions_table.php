<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_sessions', function (Blueprint $table) {
            $table->id();
            $table->char('sso_session_id', 64);
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('sid_aplikasi', 128);
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('sso_session_id')
                ->references('id')
                ->on('sso_sessions')
                ->cascadeOnDelete();
            $table->unique(['sso_session_id', 'app_id', 'sid_aplikasi'], 'app_sessions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_sessions');
    }
};
