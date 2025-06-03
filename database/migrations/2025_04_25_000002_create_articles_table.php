<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->foreignUuid('feed_uuid')->constrained('feeds', 'uuid')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('link');
            $table->text('guid')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
