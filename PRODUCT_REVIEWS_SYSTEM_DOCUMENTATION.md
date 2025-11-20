# Product Reviews System Documentation

## Table of Contents
1. [Overview](#overview)
2. [Database Schema](#database-schema)
3. [Models](#models)
4. [API Endpoints](#api-endpoints)
5. [Admin Interface](#admin-interface)
6. [Laravel Admin Interface](#laravel-admin-interface)
7. [Usage Examples](#usage-examples)
8. [Frontend Implementation](#frontend-implementation)
9. [Mobile App Implementation](#mobile-app-implementation)
10. [Testing](#testing)
11. [Troubleshooting](#troubleshooting)

## Overview

The Product Reviews System allows authenticated users to:
- Add reviews for products (one review per user per product)
- Edit their existing reviews
- Delete their reviews
- View all reviews for products
- Automatic calculation of product review statistics

### Key Features
- **One Review Per User Per Product**: Users can only review each product once
- **Automatic Statistics**: Product review count and average rating are updated automatically
- **Star Ratings**: 1-5 star rating system
- **Comprehensive API**: RESTful API for frontend/mobile integration
- **Admin Management**: Full CRUD operations through admin interface
- **Validation**: Comprehensive validation for all inputs
- **Security**: Owner-only edit/delete permissions

## Database Schema

### Reviews Table
```sql
CREATE TABLE `reviews` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `product_id` bigint(20) unsigned NOT NULL,
    `user_id` bigint(20) unsigned NOT NULL,
    `rating` int(11) NOT NULL COMMENT 'Rating between 1-5',
    `comment` text NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_product_review` (`product_id`,`user_id`),
    KEY `product_rating_index` (`product_id`,`rating`),
    KEY `user_reviews_index` (`user_id`),
    CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

### Products Table Updates
Added columns:
- `review_count` (integer, default 0) - Total number of reviews
- `average_rating` (decimal 3,2, default 0.00) - Average rating (0.00-5.00)

## Models

### Review Model
Location: `app/Models/Review.php`

**Relationships:**
- `belongsTo(Product::class)` - The reviewed product
- `belongsTo(User::class)` - The review author

**Scopes:**
- `withRating($rating)` - Filter by specific rating
- `forProduct($productId)` - Filter by product
- `byUser($userId)` - Filter by user

**Attributes:**
- `formatted_rating` - Star representation (★★★★★)
- `short_comment` - Truncated comment for lists

### Product Model Updates
Location: `app/Models/Product.php`

**New Relationships:**
- `hasMany(Review::class)` - All reviews
- `recentReviews($limit)` - Recent reviews
- `reviewsWithRating($rating)` - Reviews with specific rating

**New Methods:**
- `hasUserReviewed($userId)` - Check if user reviewed
- `getUserReview($userId)` - Get user's review
- `updateReviewStats()` - Manually update statistics
- `getFormattedRatingAttribute()` - Star representation

### User Model Updates
Location: `app/Models/User.php`

**New Relationships:**
- `hasMany(Review::class)` - All user's reviews
- `recentReviews($limit)` - User's recent reviews

**New Methods:**
- `hasReviewedProduct($productId)` - Check if reviewed product
- `getProductReview($productId)` - Get review for product

## API Endpoints

Base URL: `/api/reviews`

### 1. Get Reviews for Product
```http
GET /api/reviews?product_id={id}
```

**Parameters:**
- `product_id` (required) - Product ID
- `per_page` (optional) - Items per page (1-100, default: 10)
- `sort_by` (optional) - Sort order: `newest`, `oldest`, `highest_rating`, `lowest_rating`

**Response:**
```json
{
    "success": true,
    "message": "Reviews retrieved successfully",
    "data": {
        "reviews": [
            {
                "id": 1,
                "product_id": 1,
                "user_id": 1,
                "rating": 5,
                "comment": "Great product! Highly recommended.",
                "created_at": "2025-07-07T21:30:00.000000Z",
                "updated_at": "2025-07-07T21:30:00.000000Z",
                "formatted_rating": "★★★★★",
                "user": {
                    "id": 1,
                    "name": "John Doe"
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 10,
            "total": 1
        }
    }
}
```

### 2. Create Review (Authentication Required)
```http
POST /api/reviews
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "product_id": 1,
    "rating": 5,
    "comment": "Excellent product, would buy again!"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Review created successfully",
    "data": {
        "id": 2,
        "product_id": 1,
        "user_id": 1,
        "rating": 5,
        "comment": "Excellent product, would buy again!",
        "created_at": "2025-07-07T21:35:00.000000Z",
        "updated_at": "2025-07-07T21:35:00.000000Z",
        "user": {
            "id": 1,
            "name": "John Doe"
        },
        "product": {
            "id": 1,
            "name": "Tecno POP 9"
        }
    }
}
```

### 3. Update Review (Authentication Required)
```http
PUT /api/reviews/{review_id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "rating": 4,
    "comment": "Good product, but could be better."
}
```

### 4. Delete Review (Authentication Required)
```http
DELETE /api/reviews/{review_id}
Authorization: Bearer {token}
```

### 5. Get Review Statistics
```http
GET /api/reviews/stats?product_id={id}
```

**Response:**
```json
{
    "success": true,
    "message": "Review statistics retrieved successfully",
    "data": {
        "total_reviews": 5,
        "average_rating": 4.20,
        "rating_breakdown": {
            "5_stars": 2,
            "4_stars": 2,
            "3_stars": 1,
            "2_stars": 0,
            "1_star": 0
        }
    }
}
```

### 6. Get User's Review for Product (Authentication Required)
```http
GET /api/reviews/user-review?product_id={id}
Authorization: Bearer {token}
```

## Admin Interface

### Routes
- `GET /admin/reviews` - List all reviews
- `GET /admin/reviews/create` - Create review form
- `POST /admin/reviews` - Store new review
- `GET /admin/reviews/{review}` - View review details
- `GET /admin/reviews/{review}/edit` - Edit review form
- `PUT /admin/reviews/{review}` - Update review
- `DELETE /admin/reviews/{review}` - Delete review
- `POST /admin/reviews/bulk-delete` - Delete multiple reviews

### Features
- **Filtering**: By product, rating, search in comments
- **Pagination**: 20 reviews per page
- **Bulk Operations**: Delete multiple reviews
- **Validation**: Prevents duplicate reviews
- **Relationships**: Shows product and user details

## Laravel Admin Interface

The system includes a comprehensive Laravel Admin interface for managing reviews.

### Access
- URL: `/admin/reviews` (requires admin authentication)
- Controller: `App\Admin\Controllers\ReviewController`

### Features

#### Grid View
- **Search**: Quick search by comment text
- **Filters**: 
  - Product selection dropdown
  - User selection dropdown
  - Rating filter (1-5 stars)
  - Comment text search
  - Date range filter
- **Columns**:
  - Review ID
  - Product name
  - User name
  - Star rating display (★★★★★ format)
  - Comment excerpt (50 characters)
  - Creation date

#### Form Operations
- **Create**: Add new reviews with validation
- **Edit**: Modify existing reviews
- **Delete**: Remove reviews (with automatic stats update)
- **Validation**: 
  - Prevents duplicate product-user combinations
  - Rating must be 1-5
  - Comment is required (max 1000 characters)

#### Display Features
- **Star Rating**: Visual star display in grid
- **Relationship Loading**: Efficient loading of product and user data
- **Responsive Design**: Works on all device sizes
- **Sorting**: All columns sortable

### Usage Examples

#### Accessing the Interface
1. Login to Laravel Admin panel
2. Navigate to `/admin/reviews`
3. Use filters and search to find specific reviews
4. Click "Create" to add new reviews
5. Click edit icon to modify existing reviews

#### Bulk Operations
- Select multiple reviews using checkboxes
- Use bulk delete to remove multiple reviews at once
- All deletions trigger automatic product statistics updates

## Usage Examples

### Basic Usage in Laravel

#### 1. Create a Review
```php
use App\Models\Review;

$review = Review::create([
    'product_id' => 1,
    'user_id' => auth()->id(),
    'rating' => 5,
    'comment' => 'Amazing product!'
]);
```

#### 2. Get Product Reviews
```php
use App\Models\Product;

$product = Product::find(1);
$reviews = $product->reviews()->with('user')->latest()->paginate(10);
```

#### 3. Check if User Reviewed Product
```php
$product = Product::find(1);
$hasReviewed = $product->hasUserReviewed(auth()->id());

if (!$hasReviewed) {
    // Show review form
}
```

#### 4. Get Review Statistics
```php
$product = Product::find(1);
echo "Average Rating: " . $product->average_rating;
echo "Total Reviews: " . $product->review_count;
echo "Stars: " . $product->formatted_rating;
```

### Using Validation Requests

#### Create Review
```php
use App\Http\Requests\StoreReviewRequest;

public function store(StoreReviewRequest $request)
{
    $review = Review::create([
        'product_id' => $request->product_id,
        'user_id' => auth()->id(),
        'rating' => $request->rating,
        'comment' => $request->comment,
    ]);
    
    return response()->json(['review' => $review], 201);
}
```

#### Update Review
```php
use App\Http\Requests\UpdateReviewRequest;

public function update(UpdateReviewRequest $request, Review $review)
{
    $review->update([
        'rating' => $request->rating,
        'comment' => $request->comment,
    ]);
    
    return response()->json(['review' => $review]);
}
```

## Frontend Implementation

### Vue.js Example

#### 1. Review List Component
```vue
<template>
  <div class="reviews-section">
    <div class="review-stats">
      <div class="average-rating">
        <span class="stars">{{ formatStars(stats.average_rating) }}</span>
        <span class="rating-text">{{ stats.average_rating }} out of 5</span>
        <span class="review-count">({{ stats.total_reviews }} reviews)</span>
      </div>
    </div>

    <div class="reviews-list">
      <div v-for="review in reviews" :key="review.id" class="review-item">
        <div class="review-header">
          <strong>{{ review.user.name }}</strong>
          <span class="stars">{{ formatStars(review.rating) }}</span>
          <span class="date">{{ formatDate(review.created_at) }}</span>
        </div>
        <p class="review-comment">{{ review.comment }}</p>
      </div>
    </div>

    <button @click="loadMore" v-if="hasMoreReviews">Load More Reviews</button>
  </div>
</template>

<script>
export default {
  name: 'ReviewsList',
  props: ['productId'],
  data() {
    return {
      reviews: [],
      stats: {},
      currentPage: 1,
      hasMoreReviews: true,
      loading: false
    }
  },
  methods: {
    async fetchReviews() {
      this.loading = true;
      try {
        const response = await fetch(`/api/reviews?product_id=${this.productId}&page=${this.currentPage}`);
        const data = await response.json();
        
        if (this.currentPage === 1) {
          this.reviews = data.data.reviews;
        } else {
          this.reviews.push(...data.data.reviews);
        }
        
        this.hasMoreReviews = data.data.pagination.current_page < data.data.pagination.last_page;
      } catch (error) {
        console.error('Error fetching reviews:', error);
      }
      this.loading = false;
    },
    
    async fetchStats() {
      try {
        const response = await fetch(`/api/reviews/stats?product_id=${this.productId}`);
        const data = await response.json();
        this.stats = data.data;
      } catch (error) {
        console.error('Error fetching stats:', error);
      }
    },
    
    loadMore() {
      this.currentPage++;
      this.fetchReviews();
    },
    
    formatStars(rating) {
      return '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString();
    }
  },
  
  mounted() {
    this.fetchReviews();
    this.fetchStats();
  }
}
</script>
```

#### 2. Review Form Component
```vue
<template>
  <form @submit.prevent="submitReview" class="review-form">
    <h3>Write a Review</h3>
    
    <div class="rating-input">
      <label>Rating:</label>
      <div class="star-rating">
        <span 
          v-for="n in 5" 
          :key="n"
          @click="setRating(n)"
          :class="{ active: n <= rating }"
          class="star"
        >★</span>
      </div>
    </div>
    
    <div class="comment-input">
      <label for="comment">Comment:</label>
      <textarea 
        id="comment"
        v-model="comment"
        placeholder="Share your experience with this product..."
        rows="4"
        required
        minlength="10"
        maxlength="1000"
      ></textarea>
    </div>
    
    <button type="submit" :disabled="loading">
      {{ loading ? 'Submitting...' : 'Submit Review' }}
    </button>
    
    <div v-if="error" class="error-message">{{ error }}</div>
  </form>
</template>

<script>
export default {
  name: 'ReviewForm',
  props: ['productId'],
  data() {
    return {
      rating: 0,
      comment: '',
      loading: false,
      error: null
    }
  },
  methods: {
    setRating(value) {
      this.rating = value;
    },
    
    async submitReview() {
      if (this.rating === 0) {
        this.error = 'Please select a rating';
        return;
      }
      
      this.loading = true;
      this.error = null;
      
      try {
        const response = await fetch('/api/reviews', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.getAuthToken()}`
          },
          body: JSON.stringify({
            product_id: this.productId,
            rating: this.rating,
            comment: this.comment
          })
        });
        
        const data = await response.json();
        
        if (data.success) {
          this.$emit('review-submitted', data.data);
          this.resetForm();
        } else {
          this.error = data.message || 'Error submitting review';
        }
      } catch (error) {
        this.error = 'Network error. Please try again.';
      }
      
      this.loading = false;
    },
    
    resetForm() {
      this.rating = 0;
      this.comment = '';
    },
    
    getAuthToken() {
      // Return authentication token from your auth system
      return localStorage.getItem('auth_token');
    }
  }
}
</script>

<style scoped>
.star-rating .star {
  cursor: pointer;
  font-size: 24px;
  color: #ddd;
  transition: color 0.2s;
}

.star-rating .star.active {
  color: #ffd700;
}

.star-rating .star:hover {
  color: #ffed4e;
}
</style>
```

### React.js Example

#### Review Hook
```javascript
import { useState, useEffect } from 'react';

export const useReviews = (productId) => {
  const [reviews, setReviews] = useState([]);
  const [stats, setStats] = useState({});
  const [loading, setLoading] = useState(false);

  const fetchReviews = async (page = 1) => {
    setLoading(true);
    try {
      const response = await fetch(`/api/reviews?product_id=${productId}&page=${page}`);
      const data = await response.json();
      
      if (page === 1) {
        setReviews(data.data.reviews);
      } else {
        setReviews(prev => [...prev, ...data.data.reviews]);
      }
    } catch (error) {
      console.error('Error fetching reviews:', error);
    }
    setLoading(false);
  };

  const fetchStats = async () => {
    try {
      const response = await fetch(`/api/reviews/stats?product_id=${productId}`);
      const data = await response.json();
      setStats(data.data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  const submitReview = async (reviewData) => {
    try {
      const response = await fetch('/api/reviews', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${getAuthToken()}`
        },
        body: JSON.stringify({
          product_id: productId,
          ...reviewData
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        setReviews(prev => [data.data, ...prev]);
        fetchStats(); // Update stats
        return { success: true, data: data.data };
      } else {
        return { success: false, error: data.message };
      }
    } catch (error) {
      return { success: false, error: 'Network error' };
    }
  };

  useEffect(() => {
    fetchReviews();
    fetchStats();
  }, [productId]);

  return {
    reviews,
    stats,
    loading,
    fetchReviews,
    submitReview
  };
};

function getAuthToken() {
  return localStorage.getItem('auth_token');
}
```

## Mobile App Implementation

### React Native Example

#### Review Service
```javascript
class ReviewService {
  constructor(baseURL, authToken) {
    this.baseURL = baseURL;
    this.authToken = authToken;
  }

  async getReviews(productId, page = 1) {
    try {
      const response = await fetch(
        `${this.baseURL}/api/reviews?product_id=${productId}&page=${page}`
      );
      return await response.json();
    } catch (error) {
      throw new Error('Failed to fetch reviews');
    }
  }

  async getReviewStats(productId) {
    try {
      const response = await fetch(
        `${this.baseURL}/api/reviews/stats?product_id=${productId}`
      );
      return await response.json();
    } catch (error) {
      throw new Error('Failed to fetch review statistics');
    }
  }

  async createReview(productId, rating, comment) {
    try {
      const response = await fetch(`${this.baseURL}/api/reviews`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.authToken}`
        },
        body: JSON.stringify({
          product_id: productId,
          rating,
          comment
        })
      });
      return await response.json();
    } catch (error) {
      throw new Error('Failed to create review');
    }
  }

  async updateReview(reviewId, rating, comment) {
    try {
      const response = await fetch(`${this.baseURL}/api/reviews/${reviewId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.authToken}`
        },
        body: JSON.stringify({
          rating,
          comment
        })
      });
      return await response.json();
    } catch (error) {
      throw new Error('Failed to update review');
    }
  }

  async deleteReview(reviewId) {
    try {
      const response = await fetch(`${this.baseURL}/api/reviews/${reviewId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${this.authToken}`
        }
      });
      return await response.json();
    } catch (error) {
      throw new Error('Failed to delete review');
    }
  }
}

export default ReviewService;
```

#### Review Component
```javascript
import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, StyleSheet, TouchableOpacity, Alert } from 'react-native';
import StarRating from 'react-native-star-rating';

const ReviewsScreen = ({ productId, reviewService }) => {
  const [reviews, setReviews] = useState([]);
  const [stats, setStats] = useState({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadReviews();
    loadStats();
  }, []);

  const loadReviews = async () => {
    setLoading(true);
    try {
      const response = await reviewService.getReviews(productId);
      if (response.success) {
        setReviews(response.data.reviews);
      }
    } catch (error) {
      Alert.alert('Error', 'Failed to load reviews');
    }
    setLoading(false);
  };

  const loadStats = async () => {
    try {
      const response = await reviewService.getReviewStats(productId);
      if (response.success) {
        setStats(response.data);
      }
    } catch (error) {
      console.error('Failed to load stats:', error);
    }
  };

  const renderReview = ({ item }) => (
    <View style={styles.reviewItem}>
      <View style={styles.reviewHeader}>
        <Text style={styles.userName}>{item.user.name}</Text>
        <StarRating
          disabled={true}
          maxStars={5}
          rating={item.rating}
          starSize={16}
          fullStarColor="#FFD700"
        />
        <Text style={styles.date}>
          {new Date(item.created_at).toLocaleDateString()}
        </Text>
      </View>
      <Text style={styles.comment}>{item.comment}</Text>
    </View>
  );

  return (
    <View style={styles.container}>
      <View style={styles.statsContainer}>
        <Text style={styles.statsTitle}>Customer Reviews</Text>
        <View style={styles.statsRow}>
          <StarRating
            disabled={true}
            maxStars={5}
            rating={stats.average_rating || 0}
            starSize={20}
            fullStarColor="#FFD700"
          />
          <Text style={styles.ratingText}>
            {stats.average_rating || 0} out of 5
          </Text>
          <Text style={styles.reviewCount}>
            ({stats.total_reviews || 0} reviews)
          </Text>
        </View>
      </View>

      <FlatList
        data={reviews}
        renderItem={renderReview}
        keyExtractor={(item) => item.id.toString()}
        refreshing={loading}
        onRefresh={loadReviews}
        ListEmptyComponent={
          <Text style={styles.emptyText}>No reviews yet</Text>
        }
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 16,
    backgroundColor: '#fff',
  },
  statsContainer: {
    marginBottom: 20,
    padding: 16,
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
  },
  statsTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  statsRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  ratingText: {
    marginLeft: 8,
    fontSize: 16,
  },
  reviewCount: {
    marginLeft: 8,
    color: '#666',
  },
  reviewItem: {
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
  },
  reviewHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  userName: {
    fontWeight: 'bold',
    marginRight: 8,
  },
  date: {
    marginLeft: 'auto',
    color: '#666',
    fontSize: 12,
  },
  comment: {
    fontSize: 14,
    lineHeight: 20,
  },
  emptyText: {
    textAlign: 'center',
    color: '#666',
    fontStyle: 'italic',
    marginTop: 40,
  },
});

export default ReviewsScreen;
```

### Flutter Example

#### Review Model
```dart
class Review {
  final int id;
  final int productId;
  final int userId;
  final int rating;
  final String comment;
  final DateTime createdAt;
  final DateTime updatedAt;
  final User user;

  Review({
    required this.id,
    required this.productId,
    required this.userId,
    required this.rating,
    required this.comment,
    required this.createdAt,
    required this.updatedAt,
    required this.user,
  });

  factory Review.fromJson(Map<String, dynamic> json) {
    return Review(
      id: json['id'],
      productId: json['product_id'],
      userId: json['user_id'],
      rating: json['rating'],
      comment: json['comment'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      user: User.fromJson(json['user']),
    );
  }

  String get formattedRating {
    return '★' * rating + '☆' * (5 - rating);
  }
}

class User {
  final int id;
  final String name;

  User({required this.id, required this.name});

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
    );
  }
}
```

#### Review Service
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class ReviewService {
  final String baseUrl;
  final String? authToken;

  ReviewService({required this.baseUrl, this.authToken});

  Future<List<Review>> getReviews(int productId, {int page = 1}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/reviews?product_id=$productId&page=$page'),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      final reviews = (data['data']['reviews'] as List)
          .map((review) => Review.fromJson(review))
          .toList();
      return reviews;
    } else {
      throw Exception('Failed to load reviews');
    }
  }

  Future<Map<String, dynamic>> getReviewStats(int productId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/reviews/stats?product_id=$productId'),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return data['data'];
    } else {
      throw Exception('Failed to load review statistics');
    }
  }

  Future<Review> createReview(int productId, int rating, String comment) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/reviews'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $authToken',
      },
      body: json.encode({
        'product_id': productId,
        'rating': rating,
        'comment': comment,
      }),
    );

    if (response.statusCode == 201) {
      final data = json.decode(response.body);
      return Review.fromJson(data['data']);
    } else {
      throw Exception('Failed to create review');
    }
  }
}
```

## Testing

### Unit Tests

#### Review Model Test
```php
<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_belongs_to_product()
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Product::class, $review->product);
        $this->assertEquals($product->id, $review->product->id);
    }

    public function test_review_belongs_to_user()
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);
    }

    public function test_formatted_rating_attribute()
    {
        $review = Review::factory()->create(['rating' => 3]);
        $this->assertEquals('★★★☆☆', $review->formatted_rating);
    }

    public function test_short_comment_attribute()
    {
        $longComment = str_repeat('This is a long comment. ', 10);
        $review = Review::factory()->create(['comment' => $longComment]);
        
        $this->assertTrue(strlen($review->short_comment) <= 103); // 100 + '...'
        $this->assertStringEndsWith('...', $review->short_comment);
    }
}
```

#### Product Review Statistics Test
```php
<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_review_count_updates_automatically()
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $this->assertEquals(0, $product->fresh()->review_count);

        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        $this->assertEquals(1, $product->fresh()->review_count);
    }

    public function test_product_average_rating_calculates_correctly()
    {
        $product = Product::factory()->create();
        $users = User::factory(3)->create();

        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $users[0]->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $users[1]->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $users[2]->id,
            'rating' => 4,
        ]);

        $this->assertEquals(4.0, $product->fresh()->average_rating);
    }

    public function test_has_user_reviewed_method()
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($product->hasUserReviewed($user->id));

        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($product->hasUserReviewed($user->id));
    }
}
```

### API Tests

#### Review API Test
```php
<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ReviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_reviews_for_product()
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/reviews?product_id={$product->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'reviews' => [
                            '*' => [
                                'id',
                                'product_id',
                                'user_id',
                                'rating',
                                'comment',
                                'created_at',
                                'updated_at',
                                'user' => ['id', 'name']
                            ]
                        ],
                        'pagination'
                    ]
                ]);
    }

    public function test_authenticated_user_can_create_review()
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $reviewData = [
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Great product!',
        ];

        $response = $this->postJson('/api/reviews', $reviewData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'product_id',
                        'user_id',
                        'rating',
                        'comment',
                        'user',
                        'product'
                    ]
                ]);

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'Great product!',
        ]);
    }

    public function test_user_cannot_review_same_product_twice()
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        
        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $reviewData = [
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Another review',
        ];

        $response = $this->postJson('/api/reviews', $reviewData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['product_id']);
    }

    public function test_user_can_update_own_review()
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 3,
        ]);

        Sanctum::actingAs($user);

        $updateData = [
            'rating' => 5,
            'comment' => 'Updated review comment',
        ];

        $response = $this->putJson("/api/reviews/{$review->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'Updated review comment',
        ]);
    }

    public function test_user_cannot_update_others_review()
    {
        $product = Product::factory()->create();
        $reviewOwner = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $review = Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $reviewOwner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $updateData = [
            'rating' => 5,
            'comment' => 'Trying to update',
        ];

        $response = $this->putJson("/api/reviews/{$review->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_can_get_review_statistics()
    {
        $product = Product::factory()->create();
        $users = User::factory(3)->create();

        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $users[0]->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $users[1]->id,
            'rating' => 4,
        ]);

        $response = $this->getJson("/api/reviews/stats?product_id={$product->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'total_reviews',
                        'average_rating',
                        'rating_breakdown' => [
                            '5_stars',
                            '4_stars',
                            '3_stars',
                            '2_stars',
                            '1_star',
                        ]
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'total_reviews' => 2,
                        'average_rating' => 4.5,
                    ]
                ]);
    }
}
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ReviewApiTest.php

# Run with coverage (requires Xdebug)
php artisan test --coverage

# Run specific test method
php artisan test --filter test_can_create_review
```

### Manual Testing Checklist

#### API Testing
1. **Get Reviews**
   - [ ] Fetch reviews for existing product
   - [ ] Fetch reviews for non-existent product
   - [ ] Test pagination
   - [ ] Test sorting options

2. **Create Review**
   - [ ] Create review as authenticated user
   - [ ] Try to create review without authentication
   - [ ] Try to create duplicate review
   - [ ] Test validation (rating 1-5, comment length)

3. **Update Review**
   - [ ] Update own review
   - [ ] Try to update other's review
   - [ ] Update with invalid data

4. **Delete Review**
   - [ ] Delete own review
   - [ ] Try to delete other's review

5. **Statistics**
   - [ ] Get statistics for product with reviews
   - [ ] Get statistics for product without reviews

#### Database Testing
1. **Observer Testing**
   - [ ] Create review and verify product stats update
   - [ ] Update review rating and verify stats update
   - [ ] Delete review and verify stats update

2. **Constraint Testing**
   - [ ] Try to create duplicate review (should fail)
   - [ ] Delete product and verify reviews cascade delete
   - [ ] Delete user and verify reviews cascade delete

## Troubleshooting

### Common Issues

#### 1. Foreign Key Constraint Errors
**Problem**: Migration fails with foreign key constraint error.

**Solution**:
```php
// Ensure tables exist and use correct column types
Schema::create('reviews', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('product_id');
    $table->unsignedBigInteger('user_id');
    // ... other fields
    
    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});
```

#### 2. Observer Not Updating Product Stats
**Problem**: Product review statistics not updating automatically.

**Solution**:
1. Ensure observer is registered in `AppServiceProvider`:
```php
public function boot()
{
    Review::observe(ReviewObserver::class);
}
```

2. Clear application cache:
```bash
php artisan cache:clear
php artisan config:clear
```

#### 3. Validation Errors
**Problem**: Unique validation not working for user-product combination.

**Solution**:
```php
// In StoreReviewRequest
'product_id' => [
    'required',
    'integer',
    'exists:products,id',
    Rule::unique('reviews')->where(function ($query) {
        return $query->where('user_id', auth()->id());
    })
],
```

#### 4. Authentication Issues
**Problem**: API routes returning 401 Unauthorized.

**Solution**:
1. Ensure Sanctum is properly configured
2. Check token format: `Bearer {token}`
3. Verify middleware is applied correctly:
```php
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});
```

#### 5. Performance Issues
**Problem**: Slow loading of reviews with large datasets.

**Solutions**:
1. Add database indexes:
```php
$table->index(['product_id', 'rating']);
$table->index(['user_id']);
```

2. Use eager loading:
```php
$reviews = Review::with(['user', 'product'])->paginate(10);
```

3. Implement caching:
```php
$stats = Cache::remember("product_stats_{$productId}", 3600, function() use ($productId) {
    return $this->calculateStats($productId);
});
```

### Debugging Commands

```bash
# Check if migrations ran
php artisan migrate:status

# Check routes
php artisan route:list | grep review

# Check if observer is registered
php artisan tinker
>>> App\Models\Review::getObservableEvents()

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()

# Check review count for product
php artisan tinker
>>> App\Models\Product::find(1)->reviews()->count()
```

### Error Logging

Add logging to track issues:

```php
// In ReviewObserver
private function updateProductStats($productId)
{
    try {
        $product = \App\Models\Product::find($productId);
        if (!$product) {
            \Log::error("Product not found when updating review stats: {$productId}");
            return;
        }
        
        $reviews = Review::where('product_id', $productId);
        $product->review_count = $reviews->count();
        $product->average_rating = $reviews->count() > 0 ? $reviews->avg('rating') : 0;
        $product->save();
        
        \Log::info("Updated review stats for product {$productId}: count={$product->review_count}, avg={$product->average_rating}");
    } catch (\Exception $e) {
        \Log::error("Error updating product review stats: " . $e->getMessage());
    }
}
```

---

## Support

For additional support or questions about the Product Reviews System:

1. Check the Laravel documentation for framework-related questions
2. Review the API endpoint documentation above
3. Run the test suite to verify system functionality
4. Check the error logs for detailed error information

The system is designed to be robust and handle edge cases, but proper testing in your specific environment is recommended before production deployment.
