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
        // Immutable audit trail of soft-conflict overrides (ADR 0002). No foreign key on the
        // schedulable so the record outlives the session it justified.
        Schema::create('conflict_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('schedulable_type');
            $table->unsignedBigInteger('schedulable_id');
            $table->string('conflict_type', 30);
            $table->text('justification');
            $table->json('details');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['schedulable_type', 'schedulable_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conflict_overrides');
    }
};
