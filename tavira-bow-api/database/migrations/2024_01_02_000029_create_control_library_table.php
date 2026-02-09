<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Standard control library (reusable controls)
        Schema::create('control_library', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // CTRL-001, CTRL-002, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('control_type')->nullable();         // Preventive, Detective, Corrective
            $table->string('control_frequency')->nullable();    // Continuous, Daily, Weekly, Monthly

            // Reduction values (how much this control reduces risk)
            $table->unsignedTinyInteger('financial_reduction')->default(0);      // 0-5
            $table->unsignedTinyInteger('regulatory_reduction')->default(0);     // 0-5
            $table->unsignedTinyInteger('reputational_reduction')->default(0);   // 0-5
            $table->unsignedTinyInteger('probability_reduction')->default(0);    // 0-5

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('control_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_library');
    }
};
