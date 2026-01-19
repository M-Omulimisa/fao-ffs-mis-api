<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Project;

class CreateCycleRequest extends FormRequest
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
            // Basic Information
            'cycle_name' => 'required|string|max:200',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            
            // Saving Type - determines if share_value is required
            'saving_type' => ['required', Rule::in(['shares', 'any_amount'])],
            
            // Financial Settings
            'share_value' => 'required_if:saving_type,shares|nullable|numeric|min:100|max:1000000',
            'meeting_frequency' => ['required', Rule::in(['Weekly', 'Bi-weekly', 'Monthly'])],
            
            // Loan Settings
            'loan_interest_rate' => 'required|numeric|min:0|max:100',
            'interest_frequency' => ['required', Rule::in(['Weekly', 'Monthly'])],
            'minimum_loan_amount' => 'required|numeric|min:0',
            'maximum_loan_multiple' => 'required|integer|min:1|max:10',
            'late_payment_penalty' => 'required|numeric|min:0|max:100',
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
            'cycle_name.required' => 'Cycle name is required',
            'cycle_name.max' => 'Cycle name cannot exceed 200 characters',
            
            'start_date.required' => 'Start date is required',
            'start_date.date' => 'Start date must be a valid date',
            'start_date.after_or_equal' => 'Start date cannot be in the past',
            
            'end_date.required' => 'End date is required',
            'end_date.date' => 'End date must be a valid date',
            'end_date.after' => 'End date must be after start date',
            
            'saving_type.required' => 'Saving type is required',
            'saving_type.in' => 'Saving type must be either shares or any_amount',
            
            'share_value.required_if' => 'Share value is required when saving type is shares',
            'share_value.numeric' => 'Share value must be a number',
            'share_value.min' => 'Share value must be at least 100',
            'share_value.max' => 'Share value cannot exceed 1,000,000',
            
            'meeting_frequency.required' => 'Meeting frequency is required',
            'meeting_frequency.in' => 'Meeting frequency must be Weekly, Bi-weekly, or Monthly',
            
            'loan_interest_rate.required' => 'Loan interest rate is required',
            'loan_interest_rate.numeric' => 'Loan interest rate must be a number',
            'loan_interest_rate.min' => 'Loan interest rate cannot be negative',
            'loan_interest_rate.max' => 'Loan interest rate cannot exceed 100%',
            
            'interest_frequency.required' => 'Interest frequency is required',
            'interest_frequency.in' => 'Interest frequency must be Weekly or Monthly',
            
            'minimum_loan_amount.required' => 'Minimum loan amount is required',
            'minimum_loan_amount.numeric' => 'Minimum loan amount must be a number',
            'minimum_loan_amount.min' => 'Minimum loan amount cannot be negative',
            
            'maximum_loan_multiple.required' => 'Maximum loan multiple is required',
            'maximum_loan_multiple.integer' => 'Maximum loan multiple must be a whole number',
            'maximum_loan_multiple.min' => 'Maximum loan multiple must be at least 1',
            'maximum_loan_multiple.max' => 'Maximum loan multiple cannot exceed 10',
            
            'late_payment_penalty.required' => 'Late payment penalty is required',
            'late_payment_penalty.numeric' => 'Late payment penalty must be a number',
            'late_payment_penalty.min' => 'Late payment penalty cannot be negative',
            'late_payment_penalty.max' => 'Late payment penalty cannot exceed 100%',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = auth()->user();
            
            if (!$user) {
                $validator->errors()->add('auth', 'User not authenticated');
                return;
            }
            
            // Get user's group
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            
            if (!$userGroupId) {
                $validator->errors()->add('group', 'User is not associated with any group');
                return;
            }
            
            // Check if group already has an active cycle
            $activeCycle = Project::where('is_vsla_cycle', 'Yes')
                ->where('group_id', $userGroupId)
                ->where('is_active_cycle', 'Yes')
                ->first();
            
            if ($activeCycle) {
                $validator->errors()->add('active_cycle', 'Your group already has an active cycle: ' . $activeCycle->cycle_name . '. Please complete or close the current cycle before creating a new one.');
            }
            
            // Validate date range (minimum 3 months, maximum 24 months)
            if ($this->has('start_date') && $this->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($this->start_date);
                $endDate = \Carbon\Carbon::parse($this->end_date);
                $monthsDiff = $startDate->diffInMonths($endDate);
                
                if ($monthsDiff < 3) {
                    $validator->errors()->add('end_date', 'Cycle duration must be at least 3 months');
                }
                
                if ($monthsDiff > 24) {
                    $validator->errors()->add('end_date', 'Cycle duration cannot exceed 24 months');
                }
            }
        });
    }
}
