<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('representatives', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)
                ->index('representatives_is_default_idx');
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('representatives', function (Blueprint $table) {
            $table->dropColumn(['is_default', 'description']);
        });
    }
};
