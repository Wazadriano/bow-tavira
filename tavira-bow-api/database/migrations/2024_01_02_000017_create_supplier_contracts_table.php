<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->string('contract_ref');                           // Contract reference
            $table->text('description')->nullable();                  // Contract description
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount', 15, 2)->nullable();             // Contract amount
            $table->string('currency', 3)->default('GBP');
            $table->boolean('auto_renewal')->default(false);
            $table->integer('notice_period_days')->default(30);
            $table->boolean('alert_sent')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('end_date');
            $table->index(['end_date', 'alert_sent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_contracts');
    }
};
