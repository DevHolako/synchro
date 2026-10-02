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
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('force_single_room')->default(false)->after('state');
        });

        // The rooms an exam is split across, in the coordinator's order, with each room's
        // alphabetical range of surnames.
        Schema::create('exam_room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('allocated_students_count')->default(0);
            $table->string('first_surname', 100)->nullable();
            $table->string('last_surname', 100)->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'room_id']);
            $table->index('room_id');
        });

        // One row per student sitting the exam: their room and seat.
        Schema::create('exam_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('exam_room_assignment_id')->constrained('exam_room_assignments')->cascadeOnDelete();
            $table->unsignedInteger('seat_number');
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index('student_id');
        });

        Schema::create('exam_invigilators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('exam_room_assignment_id')->constrained('exam_room_assignments')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 20);
            $table->timestamps();

            // A teacher watches one room of an exam at most.
            $table->unique(['exam_id', 'teacher_id']);
            $table->index('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_invigilators');
        Schema::dropIfExists('exam_candidates');
        Schema::dropIfExists('exam_room_assignments');

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('force_single_room');
        });
    }
};
