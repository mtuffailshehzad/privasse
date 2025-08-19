<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_helpful_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Unique constraint to prevent duplicate votes
            $table->unique(['review_id', 'user_id']);
            
            // Indexes
            $table->index('review_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_helpful_votes');
    }
};