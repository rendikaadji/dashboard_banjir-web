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
        Schema::create('thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('scope')->default('GLOBAL'); // GLOBAL, ZONE, NODE
            $table->string('scope_ref')->nullable(); // 'RT-09' or 'WL-09-01'
            $table->tinyInteger('level'); // 1 = Waspada, 2 = Siaga, 3 = Kritis
            $table->string('metric'); // water_level_cm, rise_rate_cmh, rain_rate_mmh, flow_velocity_ms
            $table->string('operator')->default('GTE'); // GT, GTE, LT, LTE, BETWEEN
            $table->decimal('value_low', 8, 2);
            $table->decimal('value_high', 8, 2)->nullable();
            $table->integer('sustain_seconds')->default(30);
            $table->decimal('hysteresis_cm', 6, 2)->default(10.00);
            $table->integer('cooldown_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope', 'is_active', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thresholds');
    }
};
