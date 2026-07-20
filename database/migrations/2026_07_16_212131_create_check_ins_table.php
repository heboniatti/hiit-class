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
        Schema::create('check_ins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('checked_in_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('attendance_marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('attendance_marked_at')->nullable();
            $table->timestamps();

            // One row per student per class: the database itself rejects a
            // duplicate check-in. Cancelling then checking in again flips this
            // row's status back; the trail lives in audit_logs.
            $table->unique(['class_session_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
