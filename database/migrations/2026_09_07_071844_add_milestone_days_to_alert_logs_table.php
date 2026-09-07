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
        Schema::table('alert_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('milestone_days')->nullable()->after('type');
        });

        // Drop unique constraint lama — nama default Laravel dari migration awal:
        // $table->unique(['license_id', 'type', 'sent_at'])
        Schema::table('alert_logs', function (Blueprint $table) {
            $table->dropUnique('alert_logs_license_id_type_sent_at_unique');
        });

        // Generated column (stored) — MariaDB 10.2+ full support, lebih portable
        // daripada functional index langsung dan bisa dibaca Schema Builder biasa.
        Schema::table('alert_logs', function (Blueprint $table) {
            $table->date('sent_date')
                ->storedAs('DATE(created_at)')
                ->nullable()
                ->after('milestone_days');
        });

        Schema::table('alert_logs', function (Blueprint $table) {
            $table->unique(
                ['license_id', 'channel', 'milestone_days', 'sent_date'],
                'alert_logs_milestone_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alert_logs', function (Blueprint $table) {
            $table->dropUnique('alert_logs_milestone_unique');
            $table->dropColumn(['milestone_days', 'sent_date']);
        });

        Schema::table('alert_logs', function (Blueprint $table) {
            $table->unique(['license_id', 'type', 'sent_at']);
        });
    }
};
