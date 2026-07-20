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
        // The recurrence template. Concrete classes are materialised into
        // class_sessions, because each one carries its own status, capacity
        // and check-ins.
        Schema::create('class_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            // ISO weekday numbers, 1 (Monday) through 7 (Sunday).
            $table->json('days_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('max_students');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
