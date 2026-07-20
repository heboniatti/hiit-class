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
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            // Null means unlimited check-ins. Ignored by trial plans, which
            // always grant exactly one check-in per enrollment.
            $table->unsignedTinyInteger('checkins_per_week')->nullable();
            $table->decimal('price', 8, 2)->default(0);
            $table->unsignedTinyInteger('payment_due_day')->default(10);
            // When enabled, unused check-ins carry over from the previous week
            // only, capped at one week's quota.
            $table->boolean('allows_rollover')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
