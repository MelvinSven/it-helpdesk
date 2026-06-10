<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->string('title');
            $table->text('description');
            $table->foreignId('requestor_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users');
            $table->foreignId('category_id')->constrained('categories');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['new', 'in_progress', 'resolved'])->default('new');
            $table->string('attachment_path')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('assignee_id');
            $table->index('requestor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
