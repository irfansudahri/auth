<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->timestamp('waktu')->useCurrent()->index();
            $table->unsignedBigInteger('siakad_user_id')->nullable();
            $table->unsignedBigInteger('app_id')->nullable();
            $table->string('aksi', 50);
            $table->json('detail')->nullable();
            $table->string('ip', 45)->nullable();

            $table->index(['siakad_user_id', 'waktu']);
            $table->index(['aksi', 'waktu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
