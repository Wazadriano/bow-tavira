<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_item_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governance_item_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->timestamps();

            $table->unique(['governance_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_item_access');
    }
};
