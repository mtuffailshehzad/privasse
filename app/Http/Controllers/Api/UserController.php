<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'date_of_birth' => $user->date_of_birth,
                    'nationality' => $user->nationality,
                    'subscription_type' => $user->subscription_type,
                    'subscription_status' => $user->subscription_status,
                    'subscription_expires_at' => $user->subscription_expires_at,
                ],
                'subscription_active' => $user->subscription_status === 'active',
                'profile_completion' => 85 // Replace with actual logic if needed
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'nationality' => 'required|string|max:255',
            'preferred_language' => 'nullable|string|max:10',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'date_of_birth' => $user->date_of_birth,
                    'nationality' => $user->nationality,
                    'subscription_type' => $user->subscription_type,
                    'subscription_status' => $user->subscription_status,
                    'subscription_expires_at' => $user->subscription_expires_at,
                ],
                'subscription_active' => $user->subscription_status === 'active',
                'profile_completion' => 85 // Replace with actual logic if needed
            ]
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'avatar' => 'required|image|max:2048', // Max 2MB
        ]);

        // Store the avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'avatar_url' => asset('storage/' . $path),
            ]
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        // Delete the avatar
        if ($user->avatar) {
            \Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return response()->json(['success' => true, 'message' => 'Avatar deleted']);
    }

    public function getPreferences(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        // Return user preferences
        return response()->json([
            'success' => true,
            'data' => [
                'preferences' => [
                    'preferred_language' => $user->preferred_language,
                    'notification_settings' => $user->notification_settings ?? [],
                ]
            ]
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'preferred_language' => 'nullable|string|max:10',
            'notification_settings' => 'nullable|array',
        ]);

        // Update user preferences
        $user->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'preferences' => [
                    'preferred_language' => $user->preferred_language,
                    'notification_settings' => $user->notification_settings ?? [],
                ]
            ]
        ]);
    }

    public function getSubscription(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        // Return user subscription details
        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => [
                    'type' => $user->subscription_type,
                    'status' => $user->subscription_status,
                    'expires_at' => $user->subscription_expires_at,
                ]
            ]
        ]);
    }

    public function getVisits(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        // Fetch user visits (this is a placeholder, implement actual logic)
        $visits = []; // Replace with actual visit data

        return response()->json([
            'success' => true,
            'data' => [
                'visits' => $visits,
            ]
        ]);
    }

    public function deleteAccount(Request $request)
    {
        // Handle account deletion logic here
         $request->user()->delete();
        return response()->json(['message' => 'Account deleted']);
    }
}
