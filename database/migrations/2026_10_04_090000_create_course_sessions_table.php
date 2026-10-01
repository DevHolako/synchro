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
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();

            // Conflict detection (ADR 0002): overlap lookups per room and per teacher.
            $table->index(['room_id', 'starts_at']);
            $table->index(['teacher_id', 'starts_at']);
            $table->index('starts_at');
        });

        Schema::create('course_session_student_group', function (Blueprint $table) {
            $table->foreignId('course_session_id')->constrained('course_sessions')->cascadeOnDelete();
            $table->foreignId('student_group_id')->constrained('student_groups')->cascadeOnDelete();

            $table->primary(['course_session_id', 'student_group_id']);
            $table->index('student_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_session_student_group');
        Schema::dropIfExists('course_sessions');
    }
};
