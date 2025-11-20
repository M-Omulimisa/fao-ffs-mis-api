<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Only authenticated users can create reviews
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
                // Ensure user hasn't already reviewed this product
                Rule::unique('reviews')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })
            ],
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:1000|min:10',
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'product_id.unique' => 'You have already reviewed this product.',
            'rating.required' => 'Rating is required.',
            'rating.between' => 'Rating must be between 1 and 5.',
            'comment.required' => 'Comment is required.',
            'comment.min' => 'Comment must be at least 10 characters.',
            'comment.max' => 'Comment cannot exceed 1000 characters.',
        ];
    }
}
