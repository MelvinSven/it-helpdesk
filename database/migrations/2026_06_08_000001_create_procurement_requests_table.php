<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->string('employee_name');
            $table->string('requested_item');
            $table->date('request_date');
            $table->text('notes')->nullable();
            $table->string('form_file')->nullable();
            $table->timestamps();

            $table->index('request_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_requests');
    }
};
