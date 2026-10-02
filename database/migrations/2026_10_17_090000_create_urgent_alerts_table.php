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
        Schema::create('urgent_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->text('message');
            $table->string('driver');
            $table->string('status')->default('sent');
            $table->json('metadata')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['phone', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urgent_alerts');
    }
};
