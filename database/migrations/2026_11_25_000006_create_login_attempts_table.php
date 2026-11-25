<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('ip', 45);
            $table->boolean('berhasil')->default(false);
            $table->timestamp('waktu')->useCurrent();

            $table->index(['username', 'waktu']);
            $table->index(['ip', 'waktu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
