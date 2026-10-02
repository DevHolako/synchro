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
        // Retake lines (spec 05 / ticket 04): the normal session's locked final, so the student
        // keeps the better of the two.
        Schema::table('exam_grades', function (Blueprint $table) {
            $table->decimal('previous_final_grade', 4, 2)->nullable()->after('final_grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_grades', function (Blueprint $table) {
            $table->dropColumn('previous_final_grade');
        });
    }
};
