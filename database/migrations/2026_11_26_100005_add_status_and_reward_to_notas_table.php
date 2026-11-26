<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->string('status', 20)->default('Diproses')->after('jumlah');
            $table->string('status_pembayaran', 20)->default('Belum Lunas')->after('status');
            $table->unsignedInteger('diskon_nominal')->default(0)->after('diskon');
            $table->string('reward_label')->nullable()->after('diskon_nominal');
            $table->unsignedBigInteger('customer_reward_id')->nullable()->after('reward_label');
            $table->dateTime('dibatalkan_at')->nullable()->after('status_pembayaran');

            $table->index('status');
            $table->index('status_pembayaran');
        });

        Schema::table('notas', function (Blueprint $table) {
            $table->foreign('customer_reward_id')
                ->references('id')
                ->on('customer_rewards')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->dropForeign(['customer_reward_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['status_pembayaran']);
            $table->dropColumn([
                'status',
                'status_pembayaran',
                'diskon_nominal',
                'reward_label',
                'customer_reward_id',
                'dibatalkan_at',
            ]);
        });
    }
};
