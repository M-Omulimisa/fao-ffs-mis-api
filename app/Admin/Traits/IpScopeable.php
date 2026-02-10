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
        $user = Admin::user();
        return $user ? $user->ip_id : null;
    }

    /**
     * Is this admin a Super Admin (no IP restriction)?
     */
    protected function isSuperAdmin(): bool
    {
        return $this->getAdminIpId() === null;
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
     * Super admins get a dropdown to pick an IP; IP admins get auto-set.
     */
    protected function addIpFieldToForm($form): void
    {
        $ipId = $this->getAdminIpId();

        if ($ipId !== null) {
            // IP admin — auto-assign, show as readonly display
            $form->hidden('ip_id')->default($ipId);
            $form->display('ip_display', 'Implementing Partner')->with(function () use ($ipId) {
                $ip = ImplementingPartner::find($ipId);
                return $ip ? $ip->name : 'N/A';
            });
        } else {
            // Super admin — pick an IP
            $form->select('ip_id', 'Implementing Partner')
                ->options(ImplementingPartner::getDropdownOptions())
                ->help('Assign this record to an Implementing Partner');
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
