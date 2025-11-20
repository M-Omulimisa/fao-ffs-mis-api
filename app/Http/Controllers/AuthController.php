<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLoginForm()
    {
        // If already authenticated, redirect to admin dashboard
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string|min:4',
        ], [
            'username.required' => 'Username, email, or phone number is required',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 4 characters',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('username', 'remember'));
        }

        $identifier = $request->input('username');
        $password = $request->input('password');
        $remember = $request->filled('remember');

        // Try to find user by username, email, OR phone number
        $user = Administrator::where('username', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('phone_number', $identifier)
            ->first();

        if (!$user) {
            return back()
                ->withErrors(['username' => 'Invalid username, email, or phone number.'])
                ->withInput($request->only('username', 'remember'));
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            return back()
                ->withErrors(['password' => 'Incorrect password. Please try again.'])
                ->withInput($request->only('username', 'remember'));
        }

        // Check if user is explicitly inactive or suspended
        // Allow login if status is: null, empty, "Active", "1", or any other value except "Inactive", "Suspended", "Banned", "Deleted"
        $blockedStatuses = ['Inactive', 'Suspended', 'Banned', 'Deleted', 'Disabled'];
        if (isset($user->status) && in_array($user->status, $blockedStatuses)) {
            return back()
                ->withErrors(['username' => 'Your account is inactive. Please contact the administrator.'])
                ->withInput($request->only('username', 'remember'));
        }

        // Attempt to log the user in
        Auth::guard('admin')->login($user, $remember);

        // Update last login time
        $user->last_seen = now();
        $user->save();

        // Regenerate session
        $request->session()->regenerate();

        // Redirect to intended page or admin dashboard
        return redirect()->intended(admin_base_path('/'));
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
