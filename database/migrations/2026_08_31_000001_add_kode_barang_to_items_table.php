<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * kode_barang takes over as the item's identifier — the value the item list,
     * search and the borrow form key on. serial_number is demoted to an optional
     * detail field (the manufacturer serial), so it loses its unique index.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('kode_barang')->nullable()->after('id');
        });

        // Existing rows already carry their identifier in serial_number, so seed
        // kode_barang from it before the unique index goes on.
        DB::table('items')->update(['kode_barang' => DB::raw('serial_number')]);

        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['serial_number']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->change();
            $table->string('kode_barang')->nullable(false)->change();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->unique('kode_barang');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['kode_barang']);
        });

        // Fall back to kode_barang for rows that never had a serial, so the
        // NOT NULL + unique constraints can go back on.
        DB::table('items')->whereNull('serial_number')->orWhere('serial_number', '')
            ->update(['serial_number' => DB::raw('kode_barang')]);

        Schema::table('items', function (Blueprint $table) {
            $table->string('serial_number')->nullable(false)->change();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->unique('serial_number');
            $table->dropColumn('kode_barang');
        });
    }
};
