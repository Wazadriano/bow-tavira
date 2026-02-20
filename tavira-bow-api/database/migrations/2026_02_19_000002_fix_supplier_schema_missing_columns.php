<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('responsible_party_id')
                ->nullable()
                ->after('sage_category_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('supplier_access', function (Blueprint $table) {
            $table->boolean('can_create')->default(false)->after('can_edit');
            $table->boolean('can_delete')->default(false)->after('can_create');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_party_id');
        });

        Schema::table('supplier_access', function (Blueprint $table) {
            $table->dropColumn(['can_create', 'can_delete']);
        });
    }
};
