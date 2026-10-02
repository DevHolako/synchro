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
        // Emergency reschedules of published exams (ADR 0005): each one bumps the revision.
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedSmallInteger('revision')->default(1)->after('force_single_room');
        });

        // Immutable record of every emergency reschedule.
        Schema::create('exam_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('revision');
            $table->text('reason');
            $table->dateTime('previous_starts_at');
            $table->dateTime('previous_ends_at');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->json('previous_room_ids');
            $table->json('room_ids');
            $table->json('released_invigilator_ids');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['exam_id', 'revision']);
        });

        // Convocation identities replaced by a reschedule: scanning one shows "superseded".
        Schema::create('superseded_convocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->uuid('convocation_uuid')->unique();
            $table->unsignedSmallInteger('revision');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('superseded_convocations');
        Schema::dropIfExists('exam_reschedules');

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('revision');
        });
    }
};
