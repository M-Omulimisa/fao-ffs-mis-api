<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator as BaseAdministrator;

/**
 * Extended Administrator Model with SMS functionality
 */
class Administrator extends BaseAdministrator
{
    /**
     * Reset user password and send credentials via SMS
     * 
     * @return object Response object with success status and message
     */
    public function resetPasswordAndSendSMS()
    {
        $response = (object)[
            'success' => false,
            'message' => '',
            'password' => null,
            'sms_sent' => false,
            'sms_response' => null
        ];

        try {
            // Validate phone number
            if (empty($this->phone_number)) {
                $response->message = 'User has no phone number registered';
                return $response;
            }

            if (!Utils::phone_number_is_valid($this->phone_number)) {
                $response->message = 'User has invalid phone number: ' . $this->phone_number;
                return $response;
            }

            // Generate 6-digit random password
            $newPassword = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $response->password = $newPassword;

            // Hash and save password
            $this->password = password_hash($newPassword, PASSWORD_DEFAULT);
            
            try {
                $this->save();
            } catch (\Exception $e) {
                $response->message = 'Failed to save new password: ' . $e->getMessage();
                return $response;
            }

            // Prepare welcome message with credentials
            $appName = env('APP_NAME', 'DTEHM Insurance');
            $userName = $this->name ?? $this->first_name ?? 'User';
            
            $message = "Welcome to {$appName}! Your login credentials:\n"
                     . "Email: {$this->email}\n"
                     . "Password: {$newPassword}\n"
                     . "Download our app to get started!";

            // Send SMS
            $smsResponse = Utils::sendSMS($this->phone_number, $message);
            $response->sms_response = $smsResponse;
            $response->sms_sent = $smsResponse->success;

            if ($smsResponse->success) {
                $response->success = true;
                $response->message = 'Password reset successfully and SMS sent to ' . $this->phone_number;
            } else {
                $response->success = false;
                $response->message = 'Password reset but SMS failed: ' . $smsResponse->message;
            }

            return $response;

        } catch (\Exception $e) {
            $response->message = 'Error during password reset: ' . $e->getMessage();
            return $response;
        } catch (\Throwable $e) {
            $response->message = 'Critical error: ' . $e->getMessage();
            return $response;
        }
    }

    /**
     * Send welcome SMS with custom message
     * 
     * @param string|null $customMessage Custom message to send (optional)
     * @return object Response object
     */
    public function sendWelcomeSMS($customMessage = null)
    {
        $response = (object)[
            'success' => false,
            'message' => '',
            'sms_response' => null
        ];

        try {
            // Validate phone number
            if (empty($this->phone_number)) {
                $response->message = 'User has no phone number registered';
                return $response;
            }

            if (!Utils::phone_number_is_valid($this->phone_number)) {
                $response->message = 'Invalid phone number: ' . $this->phone_number;
                return $response;
            }

            // Prepare message
            $appName = env('APP_NAME', 'DTEHM Insurance');
            $userName = $this->name ?? $this->first_name ?? 'User';

            if ($customMessage) {
                $message = $customMessage;
            } else {
                $message = "Hello {$userName}! Welcome to {$appName}. "
                         . "Get comprehensive insurance coverage at your fingertips. "
                         . "Download our app today and secure your future!";
            }

            // Send SMS
            $smsResponse = Utils::sendSMS($this->phone_number, $message);
            $response->sms_response = $smsResponse;

            if ($smsResponse->success) {
                $response->success = true;
                $response->message = 'Welcome SMS sent successfully to ' . $this->phone_number;
            } else {
                $response->message = 'Failed to send SMS: ' . $smsResponse->message;
            }

            return $response;

        } catch (\Exception $e) {
            $response->message = 'Error sending welcome SMS: ' . $e->getMessage();
            return $response;
        } catch (\Throwable $e) {
            $response->message = 'Critical error: ' . $e->getMessage();
            return $response;
        }
    }
}
