<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governance_item_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('path');
            $table->integer('version')->default(1);
            $table->foreignId('uploaded_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['governance_item_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_attachments');
    }
};
