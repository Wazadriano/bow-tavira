<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_items', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique();                      // Reference number
            $table->string('activity')->nullable();                  // Activity type
            $table->text('description')->nullable();                 // Description
            $table->string('frequency')->default('Quarterly');       // GovernanceFrequency enum
            $table->string('location')->default('Global');           // GovernanceLocation enum
            $table->string('department');                            // Department
            $table->foreignId('responsible_party_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('current_status')->default('Not Started');
            $table->string('rag_status')->nullable();                // RAG status
            $table->date('deadline')->nullable();                    // Due date
            $table->date('completion_date')->nullable();             // Actual completion date
            $table->text('monthly_update')->nullable();              // Monthly update notes
            $table->json('tags')->nullable();                        // Tags array
            $table->string('file_path')->nullable();                 // Attached file path
            $table->timestamps();

            $table->index('location');
            $table->index('department');
            $table->index('frequency');
            $table->index('current_status');
            $table->index('rag_status');
            $table->index('deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_items');
    }
};
