<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kategori_pelanggan', 20)->default('Reguler')->after('alamat');
            $table->date('member_sejak')->nullable()->after('kategori_pelanggan');

            $table->index('kategori_pelanggan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['kategori_pelanggan']);
            $table->dropColumn(['kategori_pelanggan', 'member_sejak']);
        });
    }
};
