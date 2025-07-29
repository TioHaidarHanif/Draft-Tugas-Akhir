<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('role', ['admin', 'student', 'disposisi'])->default('student');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
            $table->string('nim')->nullable();
            $table->string('prodi')->nullable();
            $table->string('no_hp')->nullable();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('anonymous')->default(false);
            $table->string('judul');
            $table->text('deskripsi');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->onDelete('restrict');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nim')->nullable();
            $table->string('nama')->nullable();
            $table->string('email')->nullable();
            $table->string('prodi')->nullable();
            $table->string('semester')->nullable();
            $table->string('no_hp')->nullable();
            $table->boolean('read_by_admin')->default(false);
            $table->boolean('read_by_disposisi')->default(false);
            $table->boolean('read_by_student')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->string('token')->nullable()->unique();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->text('file_url')->nullable();
            $table->text('file_base64')->nullable();
            $table->timestamps();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });

        Schema::create('ticket_histories', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id');
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('timestamp');
            $table->string('old_priority')->nullable();
            $table->string('new_priority')->nullable();
            $table->text('comment')->nullable();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });

        Schema::create('ticket_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('text');
            $table->string('created_by_role');
            $table->timestamps();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->string('recipient_role');
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ticket_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('type');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token')->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_system_message')->default(false);
            $table->text('read_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });

        Schema::create('chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->string('file_size')->nullable();
            $table->text('file_url')->nullable();
            $table->text('file_base64')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('question');
            $table->text('answer');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ticket_id')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('to_email');
            $table->string('subject');
            $table->text('content');
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('activity');
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue');
            $table->text('payload');
            $table->integer('attempts');
            $table->integer('reserved_at')->nullable();
            $table->integer('available_at');
            $table->integer('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->text('failed_job_ids');
            $table->text('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->text('payload');
            $table->text('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('chat_attachments');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ticket_feedbacks');
        Schema::dropIfExists('ticket_histories');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('users');
        Schema::dropIfExists('sub_categories');
        Schema::dropIfExists('categories');
    }
};