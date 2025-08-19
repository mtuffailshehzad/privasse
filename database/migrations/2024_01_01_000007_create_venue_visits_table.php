<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->timestamp('visited_at');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('source')->default('app'); // app, web, qr_code, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'visited_at']);
            $table->index(['venue_id', 'visited_at']);
            $table->index(['visited_at', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_visits');
    }
};