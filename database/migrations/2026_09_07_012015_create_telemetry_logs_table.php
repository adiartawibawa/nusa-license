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
        Schema::create('telemetry_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_id')->constrained()->cascadeOnDelete();
            $table->string('request_ip', 45);
            $table->string('domain_used')->nullable();
            $table->string('app_version', 20)->nullable();
            $table->enum('result', [
                'success', 'rejected_signature', 'rejected_domain', 'rejected_status',
            ]);
            $table->jsonb('payload_meta')->nullable(); // raw metadata tambahan (jsonb utk index GIN kalau perlu)
            // $table->json('payload_meta')->nullable(); // bukan jsonb — MySQL tidak punya tipe jsonb
            $table->timestamp('pinged_at');
            $table->timestamps();

            // append-only, query utama: by license + waktu
            $table->index(['license_id', 'pinged_at']);
            $table->index(['result', 'pinged_at']); // monitoring rejected spikes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_logs');
    }
};
