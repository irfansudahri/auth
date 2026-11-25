<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_codes', function (Blueprint $table) {
            $table->char('code_hash', 64)->primary();
            $table->unsignedBigInteger('siakad_user_id');
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->char('sso_session_id', 64);
            $table->string('redirect_uri', 255);
            $table->string('state', 128);
            $table->string('code_challenge', 128)->nullable();
            $table->string('code_challenge_method', 10)->nullable();
            $table->timestamp('expired_at')->index();
            $table->timestamp('used_at')->nullable();

            $table->foreign('sso_session_id')
                ->references('id')
                ->on('sso_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_codes');
    }
};
