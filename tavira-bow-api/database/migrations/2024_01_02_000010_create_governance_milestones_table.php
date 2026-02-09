<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governance_item_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('target_date');
            $table->string('status')->default('Not Started');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['governance_item_id', 'order']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_milestones');
    }
};
