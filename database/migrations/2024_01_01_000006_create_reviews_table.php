<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned(); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment');
            $table->date('visit_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['venue_id', 'status']);
            $table->index(['user_id', 'venue_id']);
            $table->index(['rating', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index('helpful_count');

            // Unique constraint to prevent multiple reviews per user per venue
            $table->unique(['user_id', 'venue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};