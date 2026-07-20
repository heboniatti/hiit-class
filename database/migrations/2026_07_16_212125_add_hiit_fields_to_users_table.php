<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->unique()->after('name');
            $table->string('role')->default(UserRole::Student->value)->index()->after('phone');
            $table->string('status')->default(UserStatus::Active->value)->index()->after('role');
            $table->text('notes')->nullable()->after('password');
            $table->string('emergency_contact_name')->nullable()->after('notes');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
        });

        // Students authenticate with a phone number and a PIN, so an email is
        // optional book-keeping rather than an identifier.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'phone',
                'role',
                'status',
                'notes',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
