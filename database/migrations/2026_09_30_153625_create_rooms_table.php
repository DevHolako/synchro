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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->integer('floor')->nullable();
            $table->unsignedInteger('course_capacity');
            $table->unsignedInteger('exam_capacity');
            $table->boolean('has_projector')->default(false);
            $table->boolean('is_lab')->default(false);
            $table->boolean('has_computers')->default(false);
            $table->boolean('has_sound_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['building_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
