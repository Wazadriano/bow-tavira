<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // L2 - Risk Categories (e.g., P-REG-01, P-GOV-01)
        Schema::create('risk_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')
                ->constrained('risk_themes')
                ->onDelete('cascade');
            $table->string('code', 20)->unique();          // P-REG-01, P-GOV-01, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['theme_id', 'order']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_categories');
    }
};
