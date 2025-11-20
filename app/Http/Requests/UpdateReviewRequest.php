<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Only the review owner can update their review
        $review = $this->route('review');
        return auth()->check() && auth()->id() == $review->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
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
            'rating.required' => 'Rating is required.',
            'rating.between' => 'Rating must be between 1 and 5.',
            'comment.required' => 'Comment is required.',
            'comment.min' => 'Comment must be at least 10 characters.',
            'comment.max' => 'Comment cannot exceed 1000 characters.',
        ];
    }
}
