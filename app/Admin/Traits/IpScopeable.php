<?php

namespace App\Admin\Traits;

use App\Models\ImplementingPartner;
use Encore\Admin\Facades\Admin;

/**
 * Trait IpScopeable
 *
 * Mix into any Admin Controller to automatically scope queries
 * to the current admin user's Implementing Partner (ip_id).
 *
 * Super Admins (ip_id = null) see everything.
 * IP Admins see only records matching their ip_id.
 *
 * Usage in grid():
 *   $this->applyIpScope($grid);
 *
 * Usage in form():
 *   $this->setIpOnForm($form);
 */
trait IpScopeable
{
    /**
     * Get the current admin user's ip_id (null = super admin / unscoped).
     */
    protected function getAdminIpId(): ?int
    {
        // Super admins are never IP-scoped regardless of their ip_id value
        if ($this->isSuperAdmin()) {
            return null;
        }
        $user = Admin::user();
        return $user ? $user->ip_id : null;
    }

    /**
     * Is this admin a Super Admin (no IP restriction)?
     * Checks actual role slug rather than ip_id value.
     */
    protected function isSuperAdmin(): bool
    {
        $user = Admin::user();
        if (!$user) {
            return false;
        }

        // Role checks are relation-based to remain compatible with custom admin models.
        return $this->userHasRoleSlug($user, 'super_admin')
            || $this->userHasRoleSlug($user, 'administrator')
            || $this->userHasRoleId($user, 1);
    }

    /**
     * Check role slug in a way that is safe across custom admin user models.
     */
    protected function userHasRoleSlug($user, string $slug): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'roles')) {
            try {
                return $user->roles()->where('slug', $slug)->exists();
            } catch (\Throwable $e) {
                // Fallback: try already-loaded roles collection.
                try {
                    $roles = $user->roles;
                    if ($roles) {
                        return $roles->contains(function ($role) use ($slug) {
                            return isset($role->slug) && $role->slug === $slug;
                        });
                    }
                } catch (\Throwable $e2) {
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * Fallback check by role ID for environments where slugs are customized.
     */
    protected function userHasRoleId($user, int $roleId): bool
    {
        if (!$user || !method_exists($user, 'roles')) {
            return false;
        }

        try {
            return $user->roles()->where('id', $roleId)->exists();
        } catch (\Throwable $e) {
            try {
                $roles = $user->roles;
                return $roles ? $roles->contains(function ($role) use ($roleId) {
                    return isset($role->id) && (int) $role->id === $roleId;
                }) : false;
            } catch (\Throwable $e2) {
                return false;
            }
        }
    }

    /**
     * Apply IP scope to a Grid model.
     * Super admins see everything; IP admins are automatically filtered.
     */
    protected function applyIpScope($grid): void
    {
        $ipId = $this->getAdminIpId();
        if ($ipId !== null) {
            $grid->model()->where('ip_id', $ipId);
        }
    }

    /**
     * Apply IP scope to an Eloquent query builder.
     */
    protected function scopeQuery($query)
    {
        $ipId = $this->getAdminIpId();
        if ($ipId !== null) {
            $query->where('ip_id', $ipId);
        }
        return $query;
    }

    /**
     * Automatically set ip_id on a form model when creating.
     * All admin users get a dropdown to pick an IP.
     * Non-super-admins have their own IP pre-selected as default.
     */
    protected function addIpFieldToForm($form): void
    {
        $user = Admin::user();
        $ipId = $user ? $user->ip_id : null;

        if ($this->isSuperAdmin()) {
            // Super admins: dropdown with all IPs (active/inactive) so reassignment is always possible.
            $allIpOptions = ImplementingPartner::query()
                ->orderBy('name')
                ->get()
                ->mapWithKeys(function ($ip) {
                    $status = $ip->status ? strtoupper($ip->status) : 'UNKNOWN';
                    $short = $ip->short_name ? " ({$ip->short_name})" : '';
                    return [$ip->id => "{$ip->name}{$short} [{$status}]"];
                })
                ->toArray();

            $form->select('ip_id', 'Implementing Partner')
                ->options($allIpOptions)
                ->rules('required')
                ->help('Assign this record to an Implementing Partner');
        } else {
            // Non-super-admins: dropdown showing only their IP, pre-selected and locked
            $options = [];
            if ($ipId) {
                $ip = ImplementingPartner::find($ipId);
                if ($ip) {
                    $options[$ip->id] = $ip->name . ' (' . ($ip->short_name ?: 'IP') . ')';
                }
            }
            $form->select('ip_id', 'Implementing Partner')
                ->options($options)
                ->default($ipId)
                ->readOnly()
                ->rules('required')
                ->help('Auto-assigned to your Implementing Partner');
        }

        // Belt-and-suspenders: also force ip_id on save for non-super-admins
        $form->saving(function ($form) use ($ipId) {
            if ($ipId !== null && !$this->isSuperAdmin()) {
                $form->input('ip_id', $ipId);
            }
        });
    }

    /**
     * Add an IP filter to a Grid filter panel.
     * Only shown to Super Admins (IP admins are already scoped).
     */
    protected function addIpFilter($filter): void
    {
        if ($this->isSuperAdmin()) {
            $filter->equal('ip_id', 'Implementing Partner')
                ->select(ImplementingPartner::getDropdownOptions());
        }
    }

    /**
     * Verify IP ownership for a record with a direct ip_id column.
     * Super admins always pass. IP admins must own the record.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $record
     * @return bool  true if access is allowed
     */
    protected function verifyIpAccess($record): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        $ipId = $this->getAdminIpId();
        return $record && (int) $record->ip_id === $ipId;
    }

    /**
     * Verify IP ownership for a record that belongs to a group.
     * Checks the group's ip_id. Super admins always pass.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $record  Must have group_id or group relation
     * @return bool  true if access is allowed
     */
    protected function verifyIpAccessViaGroup($record): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        $ipId = $this->getAdminIpId();
        if (!$record) {
            return false;
        }
        $group = \App\Models\FfsGroup::find($record->group_id);
        return $group && (int) $group->ip_id === $ipId;
    }

    /**
     * Deny access with a redirect + toast when IP check fails.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function denyIpAccess()
    {
        admin_toastr('Access denied: this record belongs to a different Implementing Partner.', 'error');
        return redirect()->back();
    }
}
