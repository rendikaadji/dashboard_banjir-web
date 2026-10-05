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
        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->timestamp('ts')->index();
            $table->string('node_id')->index();
            $table->bigInteger('seq')->nullable();
            $table->decimal('distance_cm', 7, 2)->nullable();
            $table->decimal('water_level_cm', 7, 2)->nullable();
            $table->decimal('water_level_ewma', 7, 2)->nullable();
            $table->decimal('rise_rate_cmh', 7, 2)->nullable();
            $table->decimal('flow_lpm', 8, 2)->nullable();
            $table->decimal('flow_velocity_ms', 6, 3)->nullable();
            $table->decimal('rainfall_mm', 6, 2)->nullable();
            $table->decimal('rain_rate_mmh', 6, 2)->nullable();
            $table->decimal('wind_speed_ms', 6, 2)->nullable();
            $table->decimal('battery_v', 5, 2)->nullable();
            $table->decimal('solar_v', 5, 2)->nullable();
            $table->smallInteger('rssi')->nullable();
            $table->string('quality')->default('OK');
            $table->boolean('is_buffered')->default(false);
            $table->timestamps();

            $table->index(['node_id', 'ts']);
            $table->foreign('node_id')->references('node_id')->on('nodes')->cascadeOnDelete();
        });

        Schema::create('node_health', function (Blueprint $table) {
            $table->id();
            $table->timestamp('ts')->index();
            $table->string('node_id')->index();
            $table->bigInteger('uptime_s')->nullable();
            $table->integer('free_heap')->nullable();
            $table->string('reboot_reason')->nullable();
            $table->integer('buffer_depth')->default(0);
            $table->decimal('battery_v', 5, 2)->nullable();
            $table->smallInteger('rssi')->nullable();
            $table->timestamps();

            $table->foreign('node_id')->references('node_id')->on('nodes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_health');
        Schema::dropIfExists('readings');
    }
};
