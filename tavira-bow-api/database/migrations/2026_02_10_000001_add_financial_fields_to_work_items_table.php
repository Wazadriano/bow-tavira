<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->text('goal')->nullable()->after('description');
            $table->text('comments')->nullable()->after('monthly_update');
            $table->foreignId('department_head_id')
                ->nullable()
                ->after('responsible_party_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->decimal('cost_savings', 15, 2)->nullable()->after('priority_item');
            $table->decimal('cost_efficiency_fte', 10, 2)->nullable()->after('cost_savings');
            $table->decimal('expected_cost', 15, 2)->nullable()->after('cost_efficiency_fte');
            $table->decimal('revenue_potential', 15, 2)->nullable()->after('expected_cost');
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropForeign(['department_head_id']);
            $table->dropColumn([
                'goal',
                'comments',
                'department_head_id',
                'cost_savings',
                'cost_efficiency_fte',
                'expected_cost',
                'revenue_potential',
            ]);
        });
    }
};
