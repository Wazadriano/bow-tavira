<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->foreignId('back_up_person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('other_item_completion_dependences')->nullable();
            $table->text('issues_risks')->nullable();
            $table->string('initial_item_provider_editor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropForeign(['back_up_person_id']);
            $table->dropColumn([
                'back_up_person_id',
                'other_item_completion_dependences',
                'issues_risks',
                'initial_item_provider_editor',
            ]);
        });
    }
};
