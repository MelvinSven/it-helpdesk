<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('borrower_id')->nullable()->constrained('users');

            // Snapshot of borrow-time data so history survives later item edits.
            $table->string('item_name');
            $table->string('serial_number');
            $table->string('borrower_name');
            $table->date('borrow_date');
            $table->text('purpose'); // "Usage" in the UI; `usage` is a SQL reserved word.
            $table->string('borrow_image')->nullable();

            $table->enum('status', ['borrowed', 'returned'])->default('borrowed');

            // Return data — filled in when the item comes back.
            $table->date('return_date')->nullable();
            $table->string('return_condition')->nullable();
            $table->text('notes')->nullable();
            $table->string('return_image')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_records');
    }
};
