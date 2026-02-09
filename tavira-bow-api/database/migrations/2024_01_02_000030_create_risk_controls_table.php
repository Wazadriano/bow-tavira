<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table: risk <-> control (with implementation status)
        Schema::create('risk_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_id')->constrained()->onDelete('cascade');
            $table->foreignId('control_id')
                  ->constrained('control_library')
                  ->onDelete('cascade');
            $table->string('implementation_status')->default('Planned');  // ControlImplementationStatus
            $table->date('implementation_date')->nullable();
            $table->text('implementation_notes')->nullable();
            $table->foreignId('responsible_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            $table->unique(['risk_id', 'control_id']);
            $table->index('implementation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_controls');
    }
};
