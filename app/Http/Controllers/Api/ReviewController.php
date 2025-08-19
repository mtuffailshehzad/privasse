<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(Request $request, Venue $venue)
    {
        $user = $request->user();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = $venue->reviews()->create([
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $review
        ], 201);
    }

    public function update(Request $request, Venue $venue, Review $review)
    {
        $user = $request->user();

        if ($review->user_id !== $user->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($validated);

        return response()->json([
            'success' => true,
            'data' => $review
        ]);
    }

    public function destroy(Request $request, Venue $venue, Review $review)
    {
        $user = $request->user();

        if ($review->user_id !== $user->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['success' => true, 'message' => 'Review deleted successfully']);
    }

    public function index(Request $request, Venue $venue)
    {
        $reviews = $venue->reviews()->with('user')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    public function show(Request $request, Venue $venue, Review $review)
    {
        return response()->json([
            'success' => true,
            'data' => $review->load('user')
        ]);
    }
}
