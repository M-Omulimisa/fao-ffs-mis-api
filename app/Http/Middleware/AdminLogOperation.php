<?php

namespace App\Http\Middleware;

use Encore\Admin\Auth\Database\OperationLog as OperationLogModel;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Middleware\LogOperation;
use Illuminate\Http\Request;

/**
 * Laravel-Admin operation log that never stores secrets.
 *
 * The stock middleware json-encodes the whole request, which wrote plaintext
 * passwords into admin_operation_log (readable at /admin/auth/logs).
 */
class AdminLogOperation extends LogOperation
{
    const SECRET_FIELDS = ['password', 'password_confirmation', 'new_password', 'old_password', 'current_password', 'pin', 'otp', '_token'];

    public function handle(Request $request, \Closure $next)
    {
        if ($this->shouldLogOperation($request)) {
            try {
                OperationLogModel::create([
                    'user_id' => Admin::user()->id,
                    'path'    => substr($request->path(), 0, 255),
                    'method'  => $request->method(),
                    'ip'      => $request->getClientIp(),
                    'input'   => json_encode(self::redact($request->input())),
                ]);
            } catch (\Exception $exception) {
                // pass
            }
        }

        return $next($request);
    }

    public static function redact($input)
    {
        if (!is_array($input)) {
            return $input;
        }
        foreach ($input as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SECRET_FIELDS, true)) {
                $input[$key] = '******';
            } elseif (is_array($value)) {
                $input[$key] = self::redact($value);
            }
        }
        return $input;
    }
}
