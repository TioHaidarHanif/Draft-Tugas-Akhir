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
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('anonymous')->default(false);
            $table->string('judul');
            $table->text('deskripsi');
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->foreignId('sub_category_id')->nullable()->constrained()->onDelete('restrict');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            
            // For anonymous submissions
            $table->string('nim')->nullable();
            $table->string('nama')->nullable();
            $table->string('email')->nullable();
            $table->string('prodi')->nullable();
            $table->string('semester')->nullable();
            $table->string('no_hp')->nullable();
            
            // Read status flags
            $table->boolean('read_by_admin')->default(false);
            $table->boolean('read_by_disposisi')->default(false);
            $table->boolean('read_by_student')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
