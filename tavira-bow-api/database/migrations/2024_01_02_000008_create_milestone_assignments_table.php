<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')
                  ->constrained('task_milestones')
                  ->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['milestone_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_assignments');
    }
};
