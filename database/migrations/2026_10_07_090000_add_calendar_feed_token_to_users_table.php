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
        // Private iCal subscription (ADR 0010): feeds are looked up by the token's SHA-256 hash;
        // the token itself is kept encrypted only so its owner can copy the link again.
        Schema::table('users', function (Blueprint $table) {
            $table->text('calendar_feed_token')->nullable();
            $table->string('calendar_feed_token_hash', 64)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['calendar_feed_token_hash']);
            $table->dropColumn(['calendar_feed_token', 'calendar_feed_token_hash']);
        });
    }
};
