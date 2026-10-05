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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 'TCE'
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('code'); // 'RT-09'
            $table->string('name')->nullable();
            $table->integer('household_cnt')->nullable();
            $table->boolean('flood_prone')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
        Schema::dropIfExists('sites');
    }
};
