<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->string('no_nota', 20)->unique();
            $table->enum('jenis', ['Barang Satuan', 'Paket Kiloan']);
            $table->foreignId('pelanggan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_pelanggan');
            $table->foreignId('jenis_layanan_id')->nullable()->constrained('jenis_layanans')->nullOnDelete();
            $table->foreignId('kiloan_id')->nullable()->constrained('kiloans')->nullOnDelete();
            $table->decimal('berat', 8, 2)->nullable();
            $table->unsignedInteger('harga_per_kg')->nullable();
            $table->unsignedInteger('biaya_layanan')->default(0);
            $table->dateTime('waktu');
            $table->unsignedBigInteger('total_harga');
            $table->unsignedTinyInteger('diskon')->default(0);
            $table->unsignedBigInteger('jumlah');
            $table->timestamps();

            $table->index('waktu');
            $table->index('jenis');
            $table->index('nama_pelanggan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
