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
        Schema::create('exam_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('session_type', 20);
            $table->string('academic_year', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index('start_date');
        });

        // Exams follow the five-state lifecycle (ADR 0005); non-draft exams book their groups
        // against course sessions and other exams (ADR 0002).
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_period_id')->constrained('exam_periods')->restrictOnDelete();
            $table->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('state', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('starts_at');
            $table->index(['state', 'ends_at']);
        });

        Schema::create('exam_student_group', function (Blueprint $table) {
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_group_id')->constrained('student_groups')->restrictOnDelete();

            $table->primary(['exam_id', 'student_group_id']);
            $table->index('student_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_student_group');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('exam_periods');
    }
};
