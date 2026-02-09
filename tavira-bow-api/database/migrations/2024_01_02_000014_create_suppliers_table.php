<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('category')->nullable();
            $table->string('location')->default('Global');            // SupplierLocation enum
            $table->string('status')->default('Active');              // SupplierStatus enum
            $table->boolean('is_common_provider')->default(false);    // Multi-entity provider
            $table->foreignId('sage_category_id')
                ->nullable()
                ->constrained('sage_categories')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('location');
            $table->index('is_common_provider');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
