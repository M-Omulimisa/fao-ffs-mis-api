<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Utils;
use App\Models\Administrator;
use Illuminate\Http\Request;

class UserCredentialsController extends Controller
{
    /**
     * Send login credentials to user via SMS
     * Opens in new tab from admin user list
     * 
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\View\View
     */
    public function sendCredentials(Request $request, $userId)
    {
        $data = [
            'success' => false,
            'message' => '',
            'user' => null,
            'response' => null,
            'error' => null
        ];

        try {
            // Find user
            $user = Administrator::find($userId);

            if (!$user) {
                $data['error'] = 'User not found with ID: ' . $userId;
                return view('admin.send-credentials', $data);
            }

            $data['user'] = $user;

            // Validate phone number exists
            if (empty($user->phone_number)) {
                $data['error'] = 'User has no phone number registered. Please update user profile first.';
                return view('admin.send-credentials', $data);
            }

            // Check if phone number is valid
            if (!Utils::phone_number_is_valid($user->phone_number)) {
                $data['error'] = 'User has invalid phone number: ' . $user->phone_number;
                return view('admin.send-credentials', $data);
            }

            // Reset password and send SMS
            $resetResponse = $user->resetPasswordAndSendSMS();
            $data['response'] = $resetResponse;

            if ($resetResponse->success) {
                $data['success'] = true;
                $data['message'] = 'Login credentials have been sent successfully!';
            } else {
                $data['error'] = $resetResponse->message;
            }

            return view('admin.send-credentials', $data);

        } catch (\Exception $e) {
            $data['error'] = 'Error: ' . $e->getMessage();
            return view('admin.send-credentials', $data);
        } catch (\Throwable $e) {
            $data['error'] = 'Critical error: ' . $e->getMessage();
            return view('admin.send-credentials', $data);
        }
    }

    /**
     * Send welcome message to user via SMS
     * 
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\View\View
     */
    public function sendWelcome(Request $request, $userId)
    {
        $data = [
            'success' => false,
            'message' => '',
            'user' => null,
            'response' => null,
            'error' => null,
            'type' => 'welcome'
        ];

        try {
            // Find user
            $user = Administrator::find($userId);

            if (!$user) {
                $data['error'] = 'User not found with ID: ' . $userId;
                return view('admin.send-credentials', $data);
            }

            $data['user'] = $user;

            // Validate phone number
            if (empty($user->phone_number)) {
                $data['error'] = 'User has no phone number registered.';
                return view('admin.send-credentials', $data);
            }

            // Send welcome SMS
            $welcomeResponse = $user->sendWelcomeSMS();
            $data['response'] = $welcomeResponse;

            if ($welcomeResponse->success) {
                $data['success'] = true;
                $data['message'] = 'Welcome message sent successfully!';
            } else {
                $data['error'] = $welcomeResponse->message;
            }

            return view('admin.send-credentials', $data);

        } catch (\Exception $e) {
            $data['error'] = 'Error: ' . $e->getMessage();
            return view('admin.send-credentials', $data);
        } catch (\Throwable $e) {
            $data['error'] = 'Critical error: ' . $e->getMessage();
            return view('admin.send-credentials', $data);
        }
    }
}
