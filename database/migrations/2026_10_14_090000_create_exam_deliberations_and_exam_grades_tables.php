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
        // One grade sheet per exam (spec 05, ADR 0006): its status, then (ticket 03) its lock and PV.
        Schema::create('exam_deliberations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->unique()->constrained('exams')->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        // One row per exam candidate. Grades are out of 20 with two decimals; the final is
        // computed by the server from the module's weighting.
        Schema::create('exam_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->decimal('continuous_assessment_grade', 4, 2)->nullable();
            $table->decimal('exam_grade', 4, 2)->nullable();
            $table->decimal('final_grade', 4, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_grades');
        Schema::dropIfExists('exam_deliberations');
    }
};
