<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->text('description');
            $table->text('description_ar')->nullable();
            $table->enum('type', ['discount', 'bogo', 'free_item', 'cashback', 'points']);
            $table->enum('discount_type', ['percentage', 'fixed_amount'])->nullable();
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->decimal('original_price', 8, 2)->nullable();
            $table->decimal('discounted_price', 8, 2)->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('terms_conditions_ar')->nullable();
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('visibility_rules')->nullable();
            $table->text('qr_code')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('priority')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'is_active']);
            $table->index(['venue_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['is_featured', 'priority']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};