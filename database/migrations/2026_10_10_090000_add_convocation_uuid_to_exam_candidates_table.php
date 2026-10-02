<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The convocation's identity, in its signed QR code (ADR 0008).
        Schema::table('exam_candidates', function (Blueprint $table) {
            $table->uuid('convocation_uuid')->nullable()->unique()->after('seat_number');
        });

        DB::table('exam_candidates')->orderBy('id')->pluck('id')->each(
            fn (int $id) => DB::table('exam_candidates')->where('id', $id)->update(['convocation_uuid' => (string) Str::uuid()]),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_candidates', function (Blueprint $table) {
            $table->dropUnique(['convocation_uuid']);
            $table->dropColumn('convocation_uuid');
        });
    }
};
