<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('documentable_type');
            $table->uuid('documentable_id');
            $table->string('external_id');
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('disk')->default('public');
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->nullable()->unsigned();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id']);
            $table->unique(['documentable_type', 'documentable_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
