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
        if (!Schema::hasColumns('users', ['nim', 'prodi', 'semester', 'phone'])) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nim')->nullable()->after('email');
                $table->string('prodi')->nullable()->after('nim');
                $table->string('semester', 2)->nullable()->after('prodi');
                $table->string('phone', 15)->nullable()->after('semester');
            });
        }
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
