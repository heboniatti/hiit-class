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
        Schema::table('payments', function (Blueprint $table): void {
            $table->date('billing_period')->nullable()->after('student_plan_id');
            $table->string('receipt_path')->nullable()->after('notes');
            $table->string('receipt_original_name')->nullable()->after('receipt_path');
            $table->timestamp('receipt_uploaded_at')->nullable()->after('receipt_original_name');

            $table->unique(['user_id', 'billing_period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'billing_period']);
            $table->dropColumn([
                'billing_period',
                'receipt_path',
                'receipt_original_name',
                'receipt_uploaded_at',
            ]);
        });
    }
};
