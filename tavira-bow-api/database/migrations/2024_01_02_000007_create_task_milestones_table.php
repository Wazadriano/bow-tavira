<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('target_date');
            $table->string('status')->default('Not Started');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['work_item_id', 'order']);
            $table->index('status');
            $table->index('target_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_milestones');
    }
};
