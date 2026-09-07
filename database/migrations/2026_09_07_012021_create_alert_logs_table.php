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
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['whatsapp', 'email']);
            $table->enum('type', ['expiry_reminder', 'suspended_notice', 'expired_notice']);
            $table->enum('status', ['pending', 'sent', 'failed']);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['license_id', 'type']);
            $table->unique(['license_id', 'type', 'sent_at']); // hindari duplikat kirim per hari (opsional, sesuaikan granularity)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
    }
};
