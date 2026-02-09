<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User permissions for specific risk themes
        Schema::create('risk_theme_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('theme_id')
                  ->constrained('risk_themes')
                  ->onDelete('cascade');
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_edit_all')->default(false);     // Admin for this theme
            $table->timestamps();

            $table->unique(['user_id', 'theme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_theme_permissions');
    }
};
