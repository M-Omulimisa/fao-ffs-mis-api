<?php

namespace App\Traits;

trait PhoneNumberNormalization
{
    /**
     * Normalize phone number to Uganda format (+256...)
     * 
     * @param string $phone
     * @return string|null
     */
    protected function normalizePhone($phone)
    {
        if (empty($phone)) {
            return null;
        }
        
        // Remove all spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]+/', '', trim($phone));
        
        // If empty after cleaning, return null
        if (empty($phone)) {
            return null;
        }
        
        // Remove leading zeros
        $phone = ltrim($phone, '0');
        
        // If it starts with 256, add +
        if (substr($phone, 0, 3) === '256') {
            return '+' . $phone;
        }
        
        // If it starts with +256, return as is
        if (substr($phone, 0, 4) === '+256') {
            return $phone;
        }
        
        // If it's 9 digits (Uganda mobile without country code), add +256
        if (strlen($phone) === 9) {
            return '+256' . $phone;
        }
        
        // Otherwise, assume it needs +256 prefix
        return '+256' . $phone;
    }
    
    /**
     * Generate all possible phone number variants for searching
     * 
     * @param string $phone
     * @return array
     */
    protected function getPhoneVariants($phone)
    {
        if (empty($phone)) {
            return [];
        }
        
        $normalizedPhone = $this->normalizePhone($phone);
        
        // Get just the digits (9 digits without country code)
        $digitsOnly = preg_replace('/[^\d]/', '', $phone);
        $last9Digits = substr($digitsOnly, -9);
        
        // Generate all possible phone number variants
        $variants = [
            $phone,                          // Original input
            $normalizedPhone,                // +256XXXXXXXXX
            '0' . $last9Digits,             // 0XXXXXXXXX
            $last9Digits,                    // XXXXXXXXX
            '256' . $last9Digits,           // 256XXXXXXXXX
        ];
        
        // Remove duplicates and empty values
        return array_unique(array_filter($variants));
    }
    
    /**
     * Find user by phone number (checks multiple variants)
     * 
     * @param string $phone
     * @param string $model User model class
     * @return mixed
     */
    protected function findUserByPhone($phone, $model = \App\Models\User::class)
    {
        $variants = $this->getPhoneVariants($phone);
        $last9Digits = substr(preg_replace('/[^\d]/', '', $phone), -9);
        
        return $model::where(function($query) use ($variants, $last9Digits) {
            foreach ($variants as $variant) {
                $query->orWhere('phone_number', $variant)
                      ->orWhere('phone_number_2', $variant)
                      ->orWhere('username', $variant);
            }
            // Also search by last 9 digits to handle any format
            $query->orWhereRaw("REPLACE(REPLACE(REPLACE(phone_number, ' ', ''), '+', ''), '-', '') LIKE ?", ["%{$last9Digits}"])
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone_number_2, ' ', ''), '+', ''), '-', '') LIKE ?", ["%{$last9Digits}"])
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(username, ' ', ''), '+', ''), '-', '') LIKE ?", ["%{$last9Digits}"]);
        })->first();
    }
}
