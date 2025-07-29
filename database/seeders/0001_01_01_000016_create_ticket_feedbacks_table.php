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
        Schema::create('ticket_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->uuid('ticket_id');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('text');
            $table->string('created_by_role');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_feedbacks');
    }
};
