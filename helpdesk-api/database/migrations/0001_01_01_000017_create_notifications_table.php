<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->string('recipient_role');
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->uuid('ticket_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('type');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
