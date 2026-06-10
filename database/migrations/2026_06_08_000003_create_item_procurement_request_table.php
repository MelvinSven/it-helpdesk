<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_procurement_request', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procurement_request_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['item_id', 'procurement_request_id']);
        });

        // Carry the existing one-to-one links over into the pivot.
        DB::table('procurement_requests')->whereNotNull('item_id')->get()
            ->each(function ($row) {
                DB::table('item_procurement_request')->insert([
                    'item_id' => $row->item_id,
                    'procurement_request_id' => $row->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->after('id')
                ->constrained('items')->nullOnDelete();
        });

        // Collapse the pivot back to a single link per request (first wins).
        DB::table('item_procurement_request')->orderBy('id')->get()
            ->each(function ($row) {
                DB::table('procurement_requests')
                    ->where('id', $row->procurement_request_id)
                    ->whereNull('item_id')
                    ->update(['item_id' => $row->item_id]);
            });

        Schema::dropIfExists('item_procurement_request');
    }
};
