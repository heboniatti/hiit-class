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
        // One polymorphic trail serves both "toda alteração de pagamento deve
        // registrar quem realizou a alteração" and "toda confirmação ou
        // cancelamento de presença deve manter um histórico".
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->morphs('auditable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
