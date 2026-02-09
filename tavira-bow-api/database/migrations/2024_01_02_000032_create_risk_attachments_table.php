<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('path');
            $table->string('category')->nullable();              // 'evidence', 'documentation', 'report'
            $table->integer('version')->default(1);
            $table->foreignId('uploaded_by_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index(['risk_id', 'category']);
            $table->index(['risk_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_attachments');
    }
};
