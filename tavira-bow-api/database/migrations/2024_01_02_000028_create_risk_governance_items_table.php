<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table: risk <-> governance_item
        Schema::create('risk_governance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_id')->constrained()->onDelete('cascade');
            $table->foreignId('governance_item_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['risk_id', 'governance_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_governance_items');
    }
};
