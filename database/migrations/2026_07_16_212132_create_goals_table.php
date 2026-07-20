<?php

use App\Enums\GoalStatus;
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
        Schema::create('goals', function (Blueprint $table): void {
            $table->id();
            // Null marks a collective goal.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type');
            $table->unsignedInteger('target_value');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default(GoalStatus::Active->value);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Scopes a collective goal to a specific group of students. When empty,
        // the goal covers every active student.
        Schema::create('goal_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unique(['goal_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_user');
        Schema::dropIfExists('goals');
    }
};
