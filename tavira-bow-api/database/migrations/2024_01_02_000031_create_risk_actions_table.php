<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Risk remediation actions
        Schema::create('risk_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->date('due_date');
            $table->string('status')->default('Open');           // ActionStatus enum
            $table->string('priority')->default('Medium');        // ActionPriority enum
            $table->text('completion_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('due_date');
            $table->index(['risk_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_actions');
    }
};
