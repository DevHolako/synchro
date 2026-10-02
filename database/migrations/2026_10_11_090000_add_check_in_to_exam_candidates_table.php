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
        // Door check-in (ADR 0008): when and by whom a candidate was marked present.
        Schema::table('exam_candidates', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('convocation_uuid');
            $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_in_by');
            $table->dropColumn('checked_in_at');
        });
    }
};
