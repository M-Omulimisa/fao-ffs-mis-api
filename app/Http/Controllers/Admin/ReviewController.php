<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Review::with(['product', 'user'])->latest();
        
        // Filter by product if specified
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        // Filter by rating if specified
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }
        
        // Search by comment
        if ($request->filled('search')) {
            $query->where('comment', 'like', '%' . $request->search . '%');
        }
        
        $reviews = $query->paginate(20);
        $products = Product::select('id', 'name')->get();
        
        return view('admin.reviews.index', compact('reviews', 'products'));
    }

    /**
     * Show the form for creating a new review.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $products = Product::select('id', 'name')->get();
        $users = User::select('id', 'name', 'email')->get();
        
        return view('admin.reviews.create', compact('products', 'users'));
    }

    /**
     * Store a newly created review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:1000',
        ]);
        
        // Check if user already reviewed this product
        $existingReview = Review::where('product_id', $request->product_id)
                               ->where('user_id', $request->user_id)
                               ->first();
        
        if ($existingReview) {
            return back()->withErrors(['error' => 'This user has already reviewed this product.']);
        }
        
        Review::create($request->only(['product_id', 'user_id', 'rating', 'comment']));
        
        return redirect()->route('admin.reviews.index')
                        ->with('success', 'Review created successfully.');
    }

    /**
     * Display the specified review.
     *
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function show(Review $review)
    {
        $review->load(['product', 'user']);
        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Show the form for editing the specified review.
     *
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function edit(Review $review)
    {
        $products = Product::select('id', 'name')->get();
        $users = User::select('id', 'name', 'email')->get();
        
        return view('admin.reviews.edit', compact('review', 'products', 'users'));
    }

    /**
     * Update the specified review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Review $review)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:1000',
        ]);
        
        $review->update($request->only(['rating', 'comment']));
        
        return redirect()->route('admin.reviews.index')
                        ->with('success', 'Review updated successfully.');
    }

    /**
     * Remove the specified review from storage.
     *
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function destroy(Review $review)
    {
        $review->delete();
        
        return redirect()->route('admin.reviews.index')
                        ->with('success', 'Review deleted successfully.');
    }
    
    /**
     * Bulk delete reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'reviews' => 'required|array',
            'reviews.*' => 'exists:reviews,id'
        ]);
        
        Review::whereIn('id', $request->reviews)->delete();
        
        return redirect()->route('admin.reviews.index')
                        ->with('success', count($request->reviews) . ' reviews deleted successfully.');
    }
}
