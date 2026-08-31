<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror the items swap onto the borrow snapshot: kode_barang is the
     * identifier shown in borrow lists, serial_number is optional detail.
     */
    public function up(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->string('kode_barang')->nullable()->after('item_name');
        });

        // Snapshot columns were copied from the item at borrow time; backfill the
        // new one the same way. item_id is a cascading FK, so the row exists.
        DB::table('borrow_records')->update([
            'kode_barang' => DB::raw('(select kode_barang from items where items.id = borrow_records.item_id)'),
        ]);

        // Defensive: any row whose item vanished keeps its old snapshot value.
        DB::table('borrow_records')->whereNull('kode_barang')
            ->update(['kode_barang' => DB::raw('serial_number')]);

        Schema::table('borrow_records', function (Blueprint $table) {
            $table->string('kode_barang')->nullable(false)->change();
            $table->string('serial_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('borrow_records')->whereNull('serial_number')->orWhere('serial_number', '')
            ->update(['serial_number' => DB::raw('kode_barang')]);

        Schema::table('borrow_records', function (Blueprint $table) {
            $table->string('serial_number')->nullable(false)->change();
            $table->dropColumn('kode_barang');
        });
    }
};
