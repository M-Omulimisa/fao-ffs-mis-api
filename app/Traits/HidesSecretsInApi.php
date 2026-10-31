<?php

namespace App\Traits;

/**
 * Keeps credential fields out of API JSON.
 *
 * `password` is the bcrypt hash and `intro` holds the live password-reset /
 * verification OTP, so serialising a User (directly or as a relation such as
 * a group's facilitator) leaked both. They are hidden only for api/* requests:
 * Laravel-Admin fills its edit forms from toArray() and must keep seeing them.
 *
 * `remember_token` stays visible: the mobile app reads the login JWT from it.
 */
trait HidesSecretsInApi
{
    public function getHidden()
    {
        $hidden = parent::getHidden();

        if (app()->bound('request') && request()->is('api/*')) {
            $hidden = array_values(array_unique(array_merge($hidden, ['password', 'intro', 'otp_expires_at'])));
        }

        return $hidden;
    }
}
