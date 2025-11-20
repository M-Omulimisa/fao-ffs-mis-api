<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'sort_by' => 'sometimes|in:newest,oldest,highest_rating,lowest_rating'
        ]);

        $query = Review::where('product_id', $request->product_id)
                      ->with(['user:id,name']);

        // Apply sorting
        switch ($request->get('sort_by', 'newest')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'highest_rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest_rating':
                $query->orderBy('rating', 'asc');
                break;
            default:
                $query->latest();
        }

        $reviews = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => [
                'reviews' => $reviews->items(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ]
            ]
        ]);
    }

    /**
     * Store a newly created review.
     *
     * @param  \App\Http\Requests\StoreReviewRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $review = Review::create([
            'product_id' => $request->product_id,
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->load(['user:id,name', 'product:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => $review
        ], 201);
    }

    /**
     * Display the specified review.
     *
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Review $review): JsonResponse
    {
        $review->load(['user:id,name', 'product:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Review retrieved successfully',
            'data' => $review
        ]);
    }

    /**
     * Update the specified review.
     *
     * @param  \App\Http\Requests\UpdateReviewRequest  $request
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->load(['user:id,name', 'product:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => $review
        ]);
    }

    /**
     * Remove the specified review.
     *
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Review $review): JsonResponse
    {
        // Check if user owns the review
        if (auth()->id() !== $review->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this review'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Get review statistics for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id'
        ]);

        $product = Product::find($request->product_id);
        $reviews = Review::where('product_id', $request->product_id);

        $stats = [
            'total_reviews' => $reviews->count(),
            'average_rating' => round($reviews->avg('rating') ?: 0, 2),
            'rating_breakdown' => [
                '5_stars' => $reviews->clone()->where('rating', 5)->count(),
                '4_stars' => $reviews->clone()->where('rating', 4)->count(),
                '3_stars' => $reviews->clone()->where('rating', 3)->count(),
                '2_stars' => $reviews->clone()->where('rating', 2)->count(),
                '1_star' => $reviews->clone()->where('rating', 1)->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Review statistics retrieved successfully',
            'data' => $stats
        ]);
    }

    /**
     * Get user's review for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userReview(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id'
        ]);

        $review = Review::where('product_id', $request->product_id)
                       ->where('user_id', auth()->id())
                       ->with(['product:id,name'])
                       ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'No review found for this product'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User review retrieved successfully',
            'data' => $review
        ]);
    }
}
