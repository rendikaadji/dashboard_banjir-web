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
        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone_e164')->unique();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('role_tag')->default('WARGA'); // WARGA, KETUA_RT, SATPAM, SATGAS, PENGURUS_RW
            $table->tinyInteger('min_level')->default(2); // minimal level siaga untuk kirim
            $table->string('channel')->default('WHATSAPP');
            $table->timestamp('consent_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['zone_id', 'is_active', 'min_level']);
        });

        Schema::create('recipient_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'Grup Warga RT 09'
            $table->string('wa_group_id')->nullable();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->tinyInteger('min_level')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipient_groups');
        Schema::dropIfExists('recipients');
    }
};
