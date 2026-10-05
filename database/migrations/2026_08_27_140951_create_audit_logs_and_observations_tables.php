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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('ts')->useCurrent()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // LOGIN, THRESHOLD_UPDATE, MANUAL_ALERT, SILENCE, CALIBRATE, etc.
            $table->string('entity')->nullable();
            $table->string('entity_id')->nullable();
            $table->json('before_value')->nullable();
            $table->json('after_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['action', 'ts']);
        });

        Schema::create('manual_observations', function (Blueprint $table) {
            $table->id();
            $table->timestamp('ts')->useCurrent()->index();
            $table->string('node_id')->nullable();
            $table->decimal('manual_cm', 7, 2);
            $table->decimal('sensor_cm', 7, 2);
            $table->decimal('error_pct', 6, 3)->nullable(); // untuk hitung MAPE akurasi (PRD §23.4)
            $table->string('observer')->nullable();
            $table->string('photo_url')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('node_id')->references('node_id')->on('nodes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_observations');
        Schema::dropIfExists('audit_logs');
    }
};
