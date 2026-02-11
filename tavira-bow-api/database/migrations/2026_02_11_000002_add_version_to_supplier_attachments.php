<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_attachments', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('category');
            $table->index(['supplier_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('supplier_attachments', function (Blueprint $table) {
            $table->dropIndex(['supplier_id', 'version']);
            $table->dropColumn('version');
        });
    }
};
