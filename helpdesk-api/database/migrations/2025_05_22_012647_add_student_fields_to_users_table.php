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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nim')) {
                $table->string('nim')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'prodi')) {
                $table->string('prodi')->nullable()->after('nim');
            }
            
            if (!Schema::hasColumn('users', 'semester')) {
                $table->string('semester', 2)->nullable()->after('prodi');
            }
            
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 15)->nullable()->after('semester');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nim', 'prodi', 'semester', 'phone']);
        });
    }
};
