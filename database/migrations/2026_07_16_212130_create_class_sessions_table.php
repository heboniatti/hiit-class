<?php

use App\Enums\ClassSessionStatus;
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
        Schema::create('class_sessions', function (Blueprint $table): void {
            $table->id();
            // Null for one-off classes created outside a recurrence.
            $table->foreignId('class_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('max_students');
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default(ClassSessionStatus::Scheduled->value);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Makes materialising a recurrence idempotent, so the generator
            // command can run daily without creating duplicates.
            $table->unique(['class_schedule_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
