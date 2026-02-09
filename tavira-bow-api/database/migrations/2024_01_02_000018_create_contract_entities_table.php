<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table: contract <-> entity
        Schema::create('contract_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')
                  ->constrained('supplier_contracts')
                  ->onDelete('cascade');
            $table->string('entity');
            $table->timestamps();

            $table->unique(['contract_id', 'entity']);
            $table->index('entity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_entities');
    }
};
