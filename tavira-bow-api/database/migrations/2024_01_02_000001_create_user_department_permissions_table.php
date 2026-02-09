<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_department_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('department');
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit_status')->default(false);
            $table->boolean('can_create_tasks')->default(false);
            $table->boolean('can_edit_all')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'department']);
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_department_permissions');
    }
};
