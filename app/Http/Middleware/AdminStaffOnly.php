<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;

/**
 * Admin Staff Only Middleware
 *
 * The admin portal authenticates against the same users table as the mobile
 * app, so every farmer account can technically sign in. Only staff roles may
 * use the portal; anyone else is signed out with an explanation.
 */
class AdminStaffOnly
{
    const STAFF_ROLES = [
        'administrator',
        'ip_manager',
        'field_facilitator',
        'me_officer',
        'content_manager',
    ];

    const DENIED_MESSAGE = 'This portal is for programme staff only. Please use the FAO FFS mobile app.';

    // Built-in user/role/permission/menu/log management: super admins only
    const SUPER_ADMIN_PATHS = ['auth/users*', 'auth/roles*', 'auth/permissions*', 'auth/menu*', 'auth/logs*'];

    // Readable by staff, but changes are super-admin only (cross-partner effects)
    const SUPER_ADMIN_WRITE_PATHS = ['system-health-check/*', 'implementing-partners*'];

    public function handle(Request $request, Closure $next)
    {
        $user = Admin::user();

        // Not signed in yet (login page, assets): let admin.auth handle it
        if (!$user) {
            return $next($request);
        }

        if ($user->inRoles(self::STAFF_ROLES)) {
            if (!$user->isRole('administrator') && $this->isSuperAdminOnly($request)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['status' => 'error', 'message' => 'Only Super Admins can do this.'], 403);
                }
                admin_toastr('Only Super Admins can do this.', 'error');
                return redirect(admin_url('/'));
            }
            return $next($request);
        }

        Admin::guard()->logout();
        $request->session()->invalidate();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'error', 'message' => self::DENIED_MESSAGE], 403);
        }

        return redirect()->route('login')->withErrors(['username' => self::DENIED_MESSAGE]);
    }

    protected function isSuperAdminOnly(Request $request)
    {
        $prefix = trim(config('admin.route.prefix'), '/');
        foreach (self::SUPER_ADMIN_PATHS as $path) {
            if ($request->is($prefix . '/' . $path)) {
                return true;
            }
        }
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            foreach (self::SUPER_ADMIN_WRITE_PATHS as $path) {
                if ($request->is($prefix . '/' . $path)) {
                    return true;
                }
            }
        }
        return false;
    }
}
