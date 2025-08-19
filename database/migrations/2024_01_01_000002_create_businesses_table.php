<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('website')->nullable();
            $table->string('trade_license_number')->unique();
            $table->date('trade_license_expiry');
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('owner_phone');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->enum('subscription_type', ['basic', 'premium', 'enterprise'])->default('basic');
            $table->enum('subscription_status', ['active', 'inactive', 'expired', 'cancelled'])->default('inactive');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_women_only')->default(false);
            $table->json('settings')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'verification_status']);
            $table->index(['subscription_status', 'subscription_expires_at']);
            $table->index('is_featured');
            $table->index('is_women_only');
            $table->index('trade_license_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};