<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot the borrower's project alongside borrower_name so the borrow
     * list keeps showing the project the item was taken out for, even after
     * the user later moves to another one.
     */
    public function up(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->string('borrower_proyek')->nullable()->after('borrower_name');
        });

        // Backfill from the borrower's current project; borrower_id is nullable,
        // so rows without a linked user stay null.
        DB::table('borrow_records')->whereNotNull('borrower_id')->update([
            'borrower_proyek' => DB::raw('(select proyek from users where users.id = borrow_records.borrower_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->dropColumn('borrower_proyek');
        });
    }
};
