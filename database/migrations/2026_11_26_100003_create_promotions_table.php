<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();
            $table->text('syarat')->nullable();
            $table->string('target_kategori', 20)->nullable();
            $table->unsignedSmallInteger('target_transaksi');
            $table->string('tipe_hadiah', 20)->default('persen');
            $table->unsignedInteger('nilai_hadiah');
            $table->date('mulai')->nullable();
            $table->date('selesai')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'mulai', 'selesai']);
            $table->index('target_kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
