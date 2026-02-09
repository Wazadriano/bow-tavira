<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // L1 - Risk Themes (REG, GOV, OPS, BUS, CAP)
        Schema::create('risk_themes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();          // REG, GOV, OPS, BUS, CAP
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('board_appetite')->default(3);  // 1-5 scale
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_themes');
    }
};
