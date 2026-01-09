<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateShareoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'cycle_id' => 'required|integer|exists:projects,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'cycle_id.required' => 'Cycle ID is required',
            'cycle_id.integer' => 'Cycle ID must be a valid integer',
            'cycle_id.exists' => 'The selected cycle does not exist',
        ];
    }
}
