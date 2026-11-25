<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_app_level', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siakad_user_id');
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('level', 50);
            $table->boolean('aktif')->default(true);
            $table->date('berlaku_sampai')->nullable();
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamps();

            $table->unique(['siakad_user_id', 'app_id', 'level'], 'ual_user_app_level_unique');
            $table->index(['siakad_user_id', 'aktif', 'berlaku_sampai'], 'ual_user_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_app_level');
    }
};
