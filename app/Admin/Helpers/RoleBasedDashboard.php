<?php

namespace App\Admin\Helpers;

use Encore\Admin\Facades\Admin;

/**
 * Role-Based Dashboard Helper
 * 
 * This trait provides methods to check user roles and determine
 * what dashboard sections and features should be visible.
 * 
 * Roles:
 * - admin: Full access to everything
 * - manager: Basic overview only, no sensitive financial/payment data
 */
trait RoleBasedDashboard
{
    /**
     * Check if current user is admin
     * 
     * @return bool
     */
    protected function isAdmin()
    {
        $user = Admin::user();
        return $user && $user->isRole('admin');
    }

    /**
     * Check if current user is manager (not admin)
     * 
     * @return bool
     */
    protected function isManager()
    {
        $user = Admin::user();
        return $user && $user->isRole('manager') && !$user->isRole('admin');
    }

    /**
     * Check if current user can see financial details
     * Only admins can see detailed financial information
     * 
     * @return bool
     */
    protected function canSeeFinancialDetails()
    {
        return $this->isAdmin();
    }

    /**
     * Check if current user can see payment gateway details
     * Only admins can see payment processing details
     * 
     * @return bool
     */
    protected function canSeePaymentDetails()
    {
        return $this->isAdmin();
    }

    /**
     * Check if current user can see detailed analytics
     * Only admins can see comprehensive analytics and charts
     * 
     * @return bool
     */
    protected function canSeeDetailedAnalytics()
    {
        return $this->isAdmin();
    }

    /**
     * Check if current user can see user management details
     * Only admins can see sensitive user information
     * 
     * @return bool
     */
    protected function canSeeUserDetails()
    {
        return $this->isAdmin();
    }

    /**
     * Check if current user can manage system settings
     * Only admins can modify system configurations
     * 
     * @return bool
     */
    protected function canManageSystemSettings()
    {
        return $this->isAdmin();
    }

    /**
     * Check if current user can see specific section
     * 
     * @param string $section Section name (financial, payment, analytics, etc.)
     * @return bool
     */
    protected function canSeeSection($section)
    {
        if ($this->isAdmin()) {
            return true; // Admin sees everything
        }

        // Manager can only see basic sections
        $managerAllowedSections = [
            'kpi',              // Key Performance Indicators (basic)
            'projects_basic',   // Basic project counts only
            'insurance_basic',  // Basic insurance stats only
            'users_basic',      // Basic user counts only
        ];

        return in_array($section, $managerAllowedSections);
    }

    /**
     * Get dashboard title based on role
     * 
     * @return string
     */
    protected function getDashboardTitle()
    {
        if ($this->isAdmin()) {
            return '📊 System Dashboard - Complete Overview';
        }
        
        return '📊 Dashboard - Overview';
    }

    /**
     * Get dashboard description based on role
     * 
     * @return string
     */
    protected function getDashboardDescription()
    {
        if ($this->isAdmin()) {
            return 'Complete Real-Time Overview & Analytics';
        }
        
        return 'System Overview & Key Metrics';
    }
}
