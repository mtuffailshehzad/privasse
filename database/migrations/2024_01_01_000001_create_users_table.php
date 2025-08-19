<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('emirates_id')->nullable();
            $table->enum('preferred_language', ['en', 'ar'])->default('en');
            $table->enum('subscription_type', ['basic', 'premium', 'vip'])->nullable();
            $table->enum('subscription_status', ['active', 'inactive', 'expired', 'cancelled'])->default('inactive');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->json('preferences')->nullable();
            $table->boolean('biometric_enabled')->default(false);
            $table->boolean('two_factor_enabled')->default(false);
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('data_processing_consent')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['email', 'is_active']);
            $table->index(['phone', 'phone_verified_at']);
            $table->index(['subscription_status', 'subscription_expires_at']);
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};