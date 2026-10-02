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
        // Grade weighting (spec 05): the continuous assessment (CC) share of the final grade, in percent.
        // The final exam weighs the rest, so existing modules default to 100% final exam.
        Schema::table('modules', function (Blueprint $table) {
            $table->unsignedTinyInteger('continuous_assessment_weight')->default(0)->after('tp_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('continuous_assessment_weight');
        });
    }
};
