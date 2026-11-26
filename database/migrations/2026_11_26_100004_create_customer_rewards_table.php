<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('reward_key', 80)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('sumber', 20);
            $table->foreignId('loyalty_program_id')->nullable()->constrained('loyalty_programs')->nullOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->unsignedSmallInteger('siklus');
            $table->string('label', 150);
            $table->string('tipe_hadiah', 20);
            $table->unsignedInteger('nilai_hadiah');
            $table->string('status', 20)->default('Tersedia');
            $table->unsignedInteger('nilai_terpakai')->default(0);
            $table->dateTime('diperoleh_at');
            $table->dateTime('kedaluwarsa_at')->nullable();
            $table->dateTime('digunakan_at')->nullable();
            $table->unsignedBigInteger('nota_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['sumber', 'status']);
            $table->index('nota_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_rewards');
    }
};
