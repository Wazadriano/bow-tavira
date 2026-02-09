<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')
                  ->nullable()
                  ->constrained('supplier_contracts')
                  ->nullOnDelete();
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('status')->default('Pending');              // InvoiceStatus enum
            $table->string('frequency')->default('One Time');          // InvoiceFrequency enum
            $table->string('description')->nullable();
            $table->foreignId('sage_category_id')
                  ->nullable()
                  ->constrained('sage_categories')
                  ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('frequency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
