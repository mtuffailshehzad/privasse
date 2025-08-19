<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories');
            $table->text('address');
            $table->text('address_ar')->nullable();
            $table->string('city');
            $table->enum('emirate', ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah']);
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('amenities')->nullable();
            $table->enum('price_range', ['$', '$$', '$$$', '$$$$'])->nullable();
            $table->string('dress_code')->nullable();
            $table->string('age_restriction')->nullable();
            $table->boolean('is_women_only')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_visits')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'is_active']);
            $table->index(['category_id', 'status']);
            $table->index(['emirate', 'city']);
            $table->index(['latitude', 'longitude']);
            $table->index(['is_women_only', 'is_active']);
            $table->index(['is_featured', 'average_rating']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};