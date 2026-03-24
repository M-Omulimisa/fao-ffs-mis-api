<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebForgotPasswordController extends Controller
{
    // ── Step 1: Show the "enter your email" form ─────────────────────────────

    public function showForm()
    {
        return view('auth.forgot-password');
    }

    // ── Step 2: Validate email, send OTP, redirect to reset form ─────────────

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
        ], [
            'email.required' => 'Please enter your email address.',
        ]);

        $identifier = trim($request->input('email'));

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'No account found with that email address or username.']);
        }

        if (!$user->email) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'This account has no email address on file. Contact your administrator.']);
        }

        try {
            $user->send_password_reset();
        } catch (\Throwable $th) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Failed to send reset email: ' . $th->getMessage()]);
        }

        // Mask email for display: jo***@example.com
        $parts  = explode('@', $user->email);
        $masked = substr($parts[0], 0, 2) . str_repeat('*', max(strlen($parts[0]) - 2, 3));
        $masked .= '@' . ($parts[1] ?? '');

        // Store email in session so the reset form knows who we're resetting for
        $request->session()->put('pw_reset_email', $user->email);

        return redirect()->route('reset-password')
            ->with('otp_sent', true)
            ->with('masked_email', $masked);
    }

    // ── Step 3: Show the "enter OTP + new password" form ─────────────────────

    public function showResetForm(Request $request)
    {
        // Must have come through the email step
        if (!$request->session()->has('pw_reset_email')) {
            return redirect()->route('forgot-password')
                ->with('info', 'Please enter your email to receive a reset code first.');
        }

        return view('auth.reset-password');
    }

    // ── Step 4: Validate OTP, set new password, redirect to login ────────────

    public function resetPassword(Request $request)
    {
        $request->validate([
            'otp'                   => 'required|string|size:6',
            'password'              => 'required|string|min:6|confirmed',
        ], [
            'otp.required'          => 'Please enter the 6-digit code from your email.',
            'otp.size'              => 'The code must be exactly 6 digits.',
            'password.required'     => 'Please enter a new password.',
            'password.min'          => 'Password must be at least 6 characters.',
            'password.confirmed'    => 'Passwords do not match.',
        ]);

        $email = $request->session()->get('pw_reset_email');
        if (!$email) {
            return redirect()->route('forgot-password')
                ->withErrors(['otp' => 'Session expired. Please start again.']);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('forgot-password')
                ->withErrors(['otp' => 'Account not found. Please start again.']);
        }

        $code = trim($request->input('otp'));

        if ((string) $user->intro !== $code) {
            return back()
                ->withInput()
                ->withErrors(['otp' => 'Invalid code. Please check your email and try again.']);
        }

        if ($user->otp_expires_at && now()->isAfter($user->otp_expires_at)) {
            return back()
                ->withInput()
                ->withErrors(['otp' => 'This code has expired. Please request a new one.']);
        }

        // Update password and invalidate OTP
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password'       => password_hash($request->input('password'), PASSWORD_DEFAULT),
                'intro'          => null,
                'otp_expires_at' => null,
            ]);

        // Clear the reset session
        $request->session()->forget('pw_reset_email');

        return redirect()->route('login')
            ->with('success', 'Password reset successfully. You can now sign in with your new password.');
    }
}
