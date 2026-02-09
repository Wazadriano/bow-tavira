<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // L3 - Individual Risks
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique();              // Reference number
            $table->foreignId('category_id')
                  ->constrained('risk_categories')
                  ->onDelete('cascade');
            $table->string('name');                          // Risk name
            $table->text('description')->nullable();         // Risk description
            $table->string('tier')->nullable();              // Risk tier
            $table->foreignId('owner_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->foreignId('responsible_party_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Impact scores (1-5)
            $table->unsignedTinyInteger('financial_impact')->default(1);
            $table->unsignedTinyInteger('regulatory_impact')->default(1);
            $table->unsignedTinyInteger('reputational_impact')->default(1);

            // Probability (1-5)
            $table->unsignedTinyInteger('inherent_probability')->default(1);

            // Inherent risk
            $table->decimal('inherent_risk_score', 5, 2)->nullable();
            $table->string('inherent_rag')->nullable();      // RAG status

            // Residual risk
            $table->decimal('residual_risk_score', 5, 2)->nullable();
            $table->string('residual_rag')->nullable();      // RAG status

            // Appetite
            $table->string('appetite_status')->nullable();   // Within, Exceeds

            // Updates
            $table->text('monthly_update')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('tier');
            $table->index('inherent_rag');
            $table->index('residual_rag');
            $table->index('is_active');
            $table->index(['financial_impact', 'inherent_probability']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
