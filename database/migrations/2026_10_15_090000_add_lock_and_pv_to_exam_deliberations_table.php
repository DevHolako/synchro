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
        // Deliberation (spec 05 / ticket 03, ADR 0006): the coordinator's send-back, then the lock
        // with the weighting and figures it was deliberated with, and the archived PV.
        Schema::table('exam_deliberations', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('submitted_by');
            $table->string('return_reason', 1000)->nullable()->after('returned_at');
            $table->timestamp('locked_at')->nullable()->after('return_reason');
            $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('continuous_assessment_weight')->nullable()->after('locked_by');
            $table->decimal('class_average', 4, 2)->nullable()->after('continuous_assessment_weight');
            $table->decimal('pass_rate', 5, 2)->nullable()->after('class_average');
            $table->string('pv_document_path')->nullable()->after('pass_rate');
            $table->char('pv_sha256', 64)->nullable()->after('pv_document_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_deliberations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn([
                'returned_at', 'return_reason', 'locked_at', 'continuous_assessment_weight',
                'class_average', 'pass_rate', 'pv_document_path', 'pv_sha256',
            ]);
        });
    }
};
