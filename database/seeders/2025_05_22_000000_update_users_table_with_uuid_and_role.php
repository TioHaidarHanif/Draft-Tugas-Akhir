<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we can't drop the primary key directly, so we'll create a new table
        // and migrate data from the old one
        Schema::create('users_new', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('role', ['admin', 'student', 'disposisi'])->default('student');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Copy data from users to users_new
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            DB::table('users_new')->insert([
                'id' => Str::uuid()->toString(),
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'student',
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        // Drop the old table and rename the new one
        Schema::drop('users');
        Schema::rename('users_new', 'users');

        // Also need to update foreign keys in the sessions table
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed properly because we've changed the primary key type
        // We would need to create a new table with auto-increment id
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
