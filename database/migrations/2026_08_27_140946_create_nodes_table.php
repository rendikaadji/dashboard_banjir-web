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
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->string('node_id')->unique(); // 'WL-09-01'
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('node_type'); // WATER_LEVEL, FLOW, RAINFALL, GATEWAY
            $table->string('label')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('mount_height_cm', 7, 2)->nullable();
            $table->decimal('ground_elev_cm', 7, 2)->nullable();
            $table->decimal('calib_offset_cm', 7, 2)->default(0);
            $table->decimal('min_valid_cm', 7, 2)->nullable();
            $table->decimal('max_valid_cm', 7, 2)->nullable();
            $table->decimal('fusion_weight', 4, 3)->default(1.000);
            $table->boolean('is_critical')->default(false);
            $table->string('status')->default('ACTIVE'); // ACTIVE, STALE, OFFLINE, MAINTENANCE, RETIRED
            $table->string('firmware_version')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['zone_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
