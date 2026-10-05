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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->tinyInteger('level'); // 1 = Waspada, 2 = Siaga, 3 = Kritis
            $table->string('status')->default('ACTIVE'); // TRIGGERED, NOTIFYING, ACTIVE, ACKNOWLEDGED, RESOLVED, CANCELLED
            $table->string('trigger_type')->default('AUTO_THRESHOLD'); // AUTO_THRESHOLD, AUTO_RISE_RATE, AI_FORECAST, MANUAL
            $table->string('trigger_node_id')->nullable();
            $table->decimal('trigger_value', 8, 2)->nullable();
            $table->string('trigger_metric')->nullable();
            $table->json('snapshot')->nullable(); // kondisi semua node saat trigger
            $table->text('message_text')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->foreignId('escalated_from')->nullable()->constrained('alerts')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->boolean('is_false_alarm')->default(false);
            $table->timestamps();

            $table->index(['zone_id', 'status', 'level']);
        });

        Schema::create('alert_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('alerts')->cascadeOnDelete();
            $table->string('channel')->default('WHATSAPP'); // WHATSAPP, TELEGRAM, SIREN, LED, WEB_PUSH
            $table->string('target'); // phone or group or topic
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->integer('latency_ms')->nullable(); // trigger_at -> sent_at (target < 1000 ms)
            $table->string('status')->default('QUEUED'); // QUEUED, SENT, DELIVERED, READ, FAILED
            $table->string('error_code')->nullable();
            $table->smallInteger('retry_count')->default(0);
            $table->timestamps();

            $table->index(['alert_id', 'status', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_deliveries');
        Schema::dropIfExists('alerts');
    }
};
