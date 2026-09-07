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
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained()->cascadeOnDelete();
            $table->uuid('license_key')->unique();          // UUID v4, dipakai client
            $table->text('signing_secret');           // per-license HMAC secret (encrypted cast)
            $table->enum('status', [
                'active', 'suspended', 'expired', 'unregistered_domain',
            ])->default('active');
            $table->string('suspend_reason')->nullable();    // "tunggakan", "manual admin", dll
            $table->date('issued_at');
            $table->date('expires_at');
            $table->date('grace_period_until')->nullable();  // toleransi sebelum full lock
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_status_changed_at')->nullable();
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'expires_at']);   // untuk cron expiry scan
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
