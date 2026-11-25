<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100);
            $table->string('deskripsi', 200)->nullable();
            $table->string('ikon', 200)->nullable();
            $table->string('base_url', 255);
            $table->string('redirect_uri', 255);
            $table->string('backchannel_logout_uri', 255)->nullable();
            $table->string('client_id', 64)->unique();
            $table->string('client_secret_hash', 255);
            $table->text('hmac_secret_encrypted')->nullable();
            $table->string('ip_allowlist', 500)->nullable();
            $table->boolean('aktif')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
