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
        return $user->isRole('super_admin');
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

        $field = $form->select('ip_id', 'Implementing Partner')
            ->options(ImplementingPartner::getDropdownOptions());

        if ($this->isSuperAdmin()) {
            $field->help('Assign this record to an Implementing Partner');
        } else {
            // Pre-select the admin's own IP
            if ($ipId) {
                $field->default($ipId);
            }
            $field->help('Defaults to your Implementing Partner');
        }
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
}
