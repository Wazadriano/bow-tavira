<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique();                  // Reference number
            $table->string('type')->nullable();                  // Task type
            $table->string('activity')->nullable();              // Activity type
            $table->string('department');                        // Department/Area
            $table->text('description')->nullable();             // Task description
            $table->string('bau_or_transformative')              // BAU, Non BAU
                ->default('BAU');
            $table->string('impact_level')                       // High, Medium, Low
                ->default('Medium');
            $table->string('current_status')                     // Status
                ->default('Not Started');
            $table->string('rag_status')->nullable();            // RAG status (Blue, Green, Amber, Red)
            $table->date('deadline')->nullable();                // Due date
            $table->date('completion_date')->nullable();         // Actual completion date
            $table->text('monthly_update')->nullable();          // Monthly update notes
            $table->string('update_frequency')                   // Review frequency
                ->default('Quarterly');
            $table->foreignId('responsible_party_id')            // Task owner
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('tags')->nullable();                    // Tags array
            $table->boolean('priority_item')->default(false);    // Priority flag
            $table->string('file_path')->nullable();             // Attached file path
            $table->timestamps();

            // Indexes for common queries
            $table->index('department');
            $table->index('activity');
            $table->index('current_status');
            $table->index('rag_status');
            $table->index('priority_item');
            $table->index('deadline');
            $table->index('bau_or_transformative');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
