<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'preferred_language' => $data['preferred_language'] ?? 'en',
                'marketing_consent' => $data['marketing_consent'] ?? false,
                'data_processing_consent' => $data['data_processing_consent'] ?? true,
            ]);

            // Assign default role
            $user->assignRole('user');

            // Log registration activity
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log('User registered');

            return $user;
        });
    }

    public function login(array $credentials, bool $remember = false): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support.'
            ];
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Create token
        $tokenName = 'auth-token';
        if ($remember) {
            $tokenName = 'remember-token';
        }

        $token = $user->createToken($tokenName)->plainTextToken;

        // Log login activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged in');

        return [
            'success' => true,
            'data' => [
                'user' => $user->only([
                    'id', 'first_name', 'last_name', 'email', 'phone',
                    'preferred_language', 'subscription_type', 'subscription_status'
                ]),
                'token' => $token,
                'subscription_active' => $user->isSubscriptionActive(),
                'email_verified' => !is_null($user->email_verified_at),
                'phone_verified' => !is_null($user->phone_verified_at),
            ]
        ];
    }

    public function logout(User $user): void
    {
        // Revoke current token
        $user->currentAccessToken()->delete();

        // Log logout activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('User logged out');
    }

    public function refreshToken(User $user): string
    {
        // Revoke all existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log token refresh activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('Token refreshed');

        return $token;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        // Revoke all tokens to force re-login
        $user->tokens()->delete();

        // Log password change activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('Password changed');

        return true;
    }

    public function enableTwoFactor(User $user): array
    {
        // Generate backup codes
        $backupCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $backupCodes[] = strtoupper(substr(md5(uniqid()), 0, 8));
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_backup_codes' => encrypt(json_encode($backupCodes))
        ]);

        // Log two-factor enable activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('Two-factor authentication enabled');

        return $backupCodes;
    }

    public function disableTwoFactor(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_backup_codes' => null
        ]);

        // Log two-factor disable activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('Two-factor authentication disabled');
    }

    public function getUserActivity(User $user, int $limit = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Activity::where('causer_id', $user->id)
            ->where('causer_type', User::class)
            ->latest()
            ->paginate($limit);
    }
}