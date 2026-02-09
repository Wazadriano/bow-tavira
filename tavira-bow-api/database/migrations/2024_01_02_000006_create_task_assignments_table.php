<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('assignment_type')->default('member'); // owner, member
            $table->timestamps();

            $table->unique(['work_item_id', 'user_id']);
            $table->index('assignment_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
