<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator as BaseAdministrator;

/**
 * Extended Administrator Model with SMS functionality and critical field protection.
 *
 * IMPORTANT: This model is used by the admin guard (config/admin.php) and many
 * controllers. Any save() on an Administrator instance goes through these mutators.
 * The User model has its own identical mutators (it extends the vendor class directly).
 */
class Administrator extends BaseAdministrator
{
    use \App\Traits\TitleCase;

    // ── Critical field protection mutators ────────────────────────────────────
    // These prevent username, email, name, phone_number from being wiped to null
    // when a save() is triggered on an Administrator instance (admin guard, APIs, etc.)

    public function setNameAttribute($value): void
    {
        if (($value === null || trim($value) === '') && !empty($this->attributes['name'] ?? null)) {
            return;
        }
        $this->attributes['name'] = $value !== null ? $this->toTitleCase($value) : null;
    }

    public function setFirstNameAttribute($value): void
    {
        if (($value === null || trim($value) === '') && !empty($this->attributes['first_name'] ?? null)) {
            return;
        }
        $this->attributes['first_name'] = $value !== null ? $this->toTitleCase($value) : null;
    }

    public function setLastNameAttribute($value): void
    {
        if (($value === null || trim($value) === '') && !empty($this->attributes['last_name'] ?? null)) {
            return;
        }
        $this->attributes['last_name'] = $value !== null ? $this->toTitleCase($value) : null;
    }

    public function setEmailAttribute($value): void
    {
        if (($value === null || trim($value) === '') && !empty($this->attributes['email'] ?? null)) {
            return;
        }
        $this->attributes['email'] = $value !== null ? trim($value) : null;
    }

    public function setUsernameAttribute($value): void
    {
        if (($value === null || trim($value) === '') && !empty($this->attributes['username'] ?? null)) {
            return;
        }
        $this->attributes['username'] = $value !== null ? trim($value) : null;
    }

    public function setPhoneNumberAttribute($value): void
    {
        if (($value === null || trim($value) === '') && !empty($this->attributes['phone_number'] ?? null)) {
            return;
        }
        $this->attributes['phone_number'] = $value !== null ? trim($value) : null;
    }

    // ── Title Case accessors ─────────────────────────────────────────────────

    public function getNameAttribute($value): ?string
    {
        return $value !== null ? $this->toTitleCase($value) : null;
    }

    public function getFirstNameAttribute($value): ?string
    {
        return $value !== null ? $this->toTitleCase($value) : null;
    }

    public function getLastNameAttribute($value): ?string
    {
        return $value !== null ? $this->toTitleCase($value) : null;
    }

    /**
     * Reset user password and send credentials via email
     *
     * @return object Response object with success status and message
     */
    public function resetPasswordAndSendSMS()
    {
        $response = (object)[
            'success' => false,
            'message' => '',
            'password' => null,
            'email_sent' => false,
        ];

        try {
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

            // Send credentials via email
            try {
                Utils::send_credentials_email($this, $newPassword);
                $response->email_sent = true;
                $response->success = true;
                $response->message = 'Password reset and credentials emailed to ' . ($this->email ?: $this->username);
            } catch (\Exception $e) {
                // Password was reset, but email failed
                $response->success = true;
                $response->message = 'Password reset to ' . $newPassword . ' but email failed: ' . $e->getMessage();
            }

            return $response;

        } catch (\Exception $e) {
            $response->message = 'Error during password reset: ' . $e->getMessage();
            return $response;
        }
    }

    /**
     * Send welcome email with custom message
     *
     * @param string|null $customMessage Custom message to send (optional)
     * @return object Response object
     */
    public function sendWelcomeSMS($customMessage = null)
    {
        $response = (object)[
            'success' => false,
            'message' => '',
        ];

        try {
            Utils::send_welcome_email($this, $customMessage);
            $response->success = true;
            $response->message = 'Welcome email sent to ' . ($this->email ?: $this->username);
            return $response;

        } catch (\Exception $e) {
            $response->message = 'Failed to send welcome email: ' . $e->getMessage();
            return $response;
        }
    }
}
