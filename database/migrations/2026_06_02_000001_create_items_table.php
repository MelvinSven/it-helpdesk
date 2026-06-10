<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('item_name');
            $table->string('brand_name');
            $table->string('mac_address')->nullable();
            $table->string('type');
            $table->string('condition');
            $table->enum('status', ['available', 'borrowed'])->default('available');
            $table->string('item_image')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
