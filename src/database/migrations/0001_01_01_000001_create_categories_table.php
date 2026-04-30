<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable()->index('categories_parent_id_idx');
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->unsignedInteger('sort')->nullable()->default(0)->index('categories_sort_idx');
            $table->boolean('is_active')->nullable()->default(true)->index('categories_is_active_idx');
            $table->string('image_url')->nullable();

            // SEO fields
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 1000)->nullable();
            $table->text('seo_keywords')->nullable();

            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            // Self-referencing foreign key with null on delete
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();

            // Composite indexes for performant queries
            $table->index(['parent_id', 'is_active'], 'categories_parent_active_idx');
            $table->index(['is_active', 'sort'], 'categories_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
