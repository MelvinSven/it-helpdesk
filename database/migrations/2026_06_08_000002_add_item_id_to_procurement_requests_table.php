<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            // Optional link to the catalogued item the request is about. Set
            // from the item detail page; cleared (not deleted) if the item goes.
            $table->foreignId('item_id')->nullable()->after('id')
                ->constrained('items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('item_id');
        });
    }
};
