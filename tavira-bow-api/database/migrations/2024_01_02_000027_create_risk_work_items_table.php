<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table: risk <-> work_item (linking risks to tasks)
        Schema::create('risk_work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_item_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['risk_id', 'work_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_work_items');
    }
};
