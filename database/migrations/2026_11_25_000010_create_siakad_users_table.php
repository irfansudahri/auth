<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siakad_users', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_key', 100)->default('default');
            $table->string('userid', 50);
            $table->string('password', 255);
            $table->string('nama_lengkap', 150);
            $table->string('foto', 100)->nullable();
            $table->string('level', 30)->default('mahasiswa');
            $table->enum('blokir', ['Y', 'N'])->default('N');
            $table->dateTime('last_login')->nullable();
            $table->timestamps();

            $table->unique(['tenant_key', 'userid'], 'siakad_users_tenant_userid_unique');
            $table->index('userid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siakad_users');
    }
};
