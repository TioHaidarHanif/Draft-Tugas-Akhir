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
        Schema::table('ticket_histories', function (Blueprint $table) {
            $table->string('old_priority')->nullable()->after('new_status');
            $table->string('new_priority')->nullable()->after('old_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_histories', function (Blueprint $table) {
            $table->dropColumn(['old_priority', 'new_priority']);
        });
    }
};
