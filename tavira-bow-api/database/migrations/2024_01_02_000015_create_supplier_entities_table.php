<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table: supplier <-> entity (multi-entity support)
        Schema::create('supplier_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->string('entity');  // Entity name/code
            $table->timestamps();

            $table->unique(['supplier_id', 'entity']);
            $table->index('entity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_entities');
    }
};
