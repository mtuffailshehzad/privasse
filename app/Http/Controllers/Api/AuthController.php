<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $authService;
    protected $smsService;

    public function __construct(AuthService $authService, SmsService $smsService)
    {
        $this->authService = $authService;
        $this->smsService = $smsService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());
            
            // Send OTP for phone verification
            $this->smsService->sendOtp($user->phone);
            
            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Please verify your phone number.',
                'data' => [
                    'user' => $user->only(['id', 'first_name', 'last_name', 'email', 'phone']),
                    'requires_verification' => true
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->only('email', 'password');
            $result = $this->authService->login($credentials, $request->remember_me ?? false);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $result['data']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink($request->only('email'));
            
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link sent to your email'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to send reset link'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid reset token'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $request->validate(['phone' => 'required|string']);
            
            $this->smsService->sendOtp($request->phone);
            
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $isValid = $this->smsService->verifyOtp($request->phone, $request->otp);
            
            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP'
                ], 422);
            }

            // Update user phone verification status
            $user = User::where('phone', $request->phone)->first();
            if ($user) {
                $user->update(['phone_verified_at' => now()]);
                
                // Generate token if user is not authenticated
                if (!auth()->check()) {
                    $token = $user->createToken('auth-token')->plainTextToken;
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Phone verified successfully',
                        'data' => [
                            'user' => $user->only(['id', 'first_name', 'last_name', 'email', 'phone']),
                            'token' => $token
                        ]
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Phone verified successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->tokens()->delete();
            
            $token = $user->createToken('auth-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => $user->only(['id', 'first_name', 'last_name', 'email', 'phone'])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['media', 'subscriptions' => function($query) {
                $query->where('status', 'active')->latest();
            }]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'subscription_active' => $user->isSubscriptionActive(),
                    'profile_completion' => $this->calculateProfileCompletion($user)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateProfileCompletion(User $user): int
    {
        $fields = ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'nationality'];
        $completed = 0;
        
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completed++;
            }
        }
        
        if ($user->email_verified_at) $completed++;
        if ($user->phone_verified_at) $completed++;
        if ($user->getFirstMedia('avatar')) $completed++;
        
        return round(($completed / 9) * 100);
    }
}