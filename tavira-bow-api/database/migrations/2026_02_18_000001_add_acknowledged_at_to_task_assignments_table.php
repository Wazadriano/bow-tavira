<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->timestamp('acknowledged_at')->nullable()->after('assignment_type');
        });
    }

    public function down(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->dropColumn('acknowledged_at');
        });
    }
};
