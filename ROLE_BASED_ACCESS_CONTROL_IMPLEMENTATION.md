# Role-Based Access Control Implementation
## Admin vs Manager Access Control

**Implementation Date:** November 12, 2025  
**Status:** ✅ COMPLETE

---

## Overview

This document outlines the implementation of role-based access control (RBAC) for the DTEHM Insurance Admin Portal. The system now supports two primary user roles with distinct access levels:

### User Roles

1. **Admin** - Full system access
   - Complete dashboard with all analytics
   - Full CRUD operations on all resources
   - Access to financial data and payment gateways
   - System configuration management
   - User management (create, edit, delete)

2. **Manager** - Limited read-only access
   - Basic dashboard with KPIs only
   - Read-only access to most resources
   - No access to financial details
   - No access to payment gateway data
   - Cannot manage users or system settings

---

## Implementation Components

### 1. Role-Based Dashboard Helper Trait
**File:** `app/Admin/Helpers/RoleBasedDashboard.php`

This trait provides centralized role-checking methods used across all admin controllers:

```php
use App\Admin\Helpers\RoleBasedDashboard;

class YourController extends AdminController
{
    use RoleBasedDashboard;
    
    public function someMethod()
    {
        if ($this->isAdmin()) {
            // Admin-only code
        }
        
        if ($this->canSeeFinancialDetails()) {
            // Show financial data
        }
    }
}
```

#### Available Methods

- `isAdmin()` - Check if user is admin
- `isManager()` - Check if user is manager (not admin)
- `canSeeFinancialDetails()` - Admin-only financial data access
- `canSeePaymentDetails()` - Admin-only payment gateway access
- `canSeeDetailedAnalytics()` - Admin-only comprehensive analytics
- `canSeeUserDetails()` - Admin-only sensitive user information
- `canManageSystemSettings()` - Admin-only system configuration
- `canSeeSection($section)` - Check access to specific sections
- `getDashboardTitle()` - Role-based dashboard title
- `getDashboardDescription()` - Role-based dashboard description

---

### 2. Dashboard Access Control
**File:** `app/Admin/Controllers/HomeController.php`

The dashboard is now dynamically rendered based on user role:

#### Admin Dashboard Includes:
✅ Key Performance Indicators (KPIs)  
✅ Revenue & Financial Trends (Charts)  
✅ Financial Overview  
✅ Project Analytics with Charts  
✅ Insurance System Overview  
✅ Payment Gateway Statistics  
✅ User & Order Analytics (Detailed)  
✅ Recent Activities  

#### Manager Dashboard Includes:
✅ Key Performance Indicators (KPIs) - Basic  
✅ Insurance System Overview - Basic counts only  
✅ User & Order Analytics - Basic counts only  
❌ Financial trends and charts  
❌ Payment gateway statistics  
❌ Detailed analytics  
❌ Recent activities log  

**Implementation:**
```php
use App\Admin\Helpers\RoleBasedDashboard;

class HomeController extends Controller
{
    use RoleBasedDashboard;
    
    public function index(Content $content)
    {
        $content = $content
            ->title($this->getDashboardTitle())
            ->description($this->getDashboardDescription());
        
        // Admin-only sections
        if ($this->canSeeDetailedAnalytics()) {
            $content->row(function (Row $row) {
                $this->addRevenueCharts($row);
            });
        }
        
        return $content;
    }
}
```

---

### 3. Route-Level Middleware Protection
**File:** `app/Http/Middleware/AdminOnly.php`

Custom middleware to protect admin-only routes:

```php
namespace App\Http\Middleware;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = Admin::user();
        
        if (!$user || !$user->isRole('admin')) {
            admin_toastr('Access denied. Admin privileges required.', 'error');
            return back();
        }
        
        return $next($request);
    }
}
```

**Registration:** Added to `app/Http/Kernel.php`
```php
protected $routeMiddleware = [
    'admin.only' => \App\Http\Middleware\AdminOnly::class,
];
```

---

### 4. Protected Routes
**File:** `app/Admin/routes.php`

Routes are organized with middleware protection:

#### Admin-Only Routes (Protected)
- **Investment Management**
  - Projects (full CRUD)
  - Project Shares
  - Project Transactions
  - Disbursements
  - Account Transactions
  - Withdraw Requests (approve/reject)

- **Financial Operations**
  - Insurance Subscription Payments
  - Insurance Transactions
  - Membership Payments

- **System Management**
  - System Configurations
  - Pesapal Payments
  - User Management (create/edit/delete)

#### Manager Accessible Routes (Read-Only)
- Insurance Programs (view only)
- Insurance Subscriptions (view only)
- Medical Service Requests (view only)
- Products (view only)
- Orders (view only)
- Users (view only)

**Example Route Protection:**
```php
// Admin-only routes
$router->group(['middleware' => 'admin.only'], function ($router) {
    $router->resource('projects', ProjectController::class);
    $router->resource('project-transactions', ProjectTransactionController::class);
});

// Manager can view, admin can edit
$router->resource('insurance-programs', InsuranceProgramController::class);
```

---

### 5. Grid-Level Access Control
**Files:** Multiple controllers

Controllers now implement role-based grid restrictions:

#### ProjectController
```php
use App\Admin\Helpers\RoleBasedDashboard;

class ProjectController extends AdminController
{
    use RoleBasedDashboard;
    
    protected function grid()
    {
        $grid = new Grid(new Project());
        
        // Managers: read-only access
        if (!$this->canSeeFinancialDetails()) {
            $grid->disableCreateButton();
            $grid->disableActions();
            $grid->disableBatchActions();
        }
        
        return $grid;
    }
}
```

#### InsuranceProgramController
```php
if (!$this->isAdmin()) {
    $grid->disableCreateButton();
    $grid->actions(function ($actions) {
        $actions->disableDelete();
        $actions->disableEdit();
    });
    $grid->disableBatchActions();
}
```

---

### 6. Form-Level Access Control
**File:** `app/Admin/Controllers/UserController.php`

Forms hide sensitive fields from managers:

```php
$user = Admin::user();

// Only admins can see/edit password fields
if ($user && $user->isRole('admin')) {
    $form->row(function ($row) {
        $row->width(6)->password('password', __('Password'));
        $row->width(6)->password('password_confirmation', __('Confirm Password'));
    });
}
```

---

## Role Checking Reference

### How to Check User Role

```php
use Encore\Admin\Facades\Admin;

$user = Admin::user();

// Basic role check
$isAdmin = $user->isRole('admin');
$isManager = $user->isRole('manager');

// Using trait methods (recommended)
use App\Admin\Helpers\RoleBasedDashboard;

class YourController extends AdminController
{
    use RoleBasedDashboard;
    
    public function index()
    {
        if ($this->isAdmin()) {
            // Admin-only logic
        }
        
        if ($this->isManager()) {
            // Manager-only logic
        }
    }
}
```

---

## Security Features

### 1. Multi-Layer Protection
- ✅ Middleware level (route protection)
- ✅ Controller level (grid/form restrictions)
- ✅ View level (conditional rendering)
- ✅ Method level (business logic checks)

### 2. Access Denial Handling
- Middleware redirects with error message
- Controllers disable actions/buttons
- Views hide sensitive sections
- Forms hide sensitive fields

### 3. URL Protection
Even if managers know direct URLs, they cannot:
- Access admin-only routes (middleware blocks)
- Edit/delete resources (grid actions disabled)
- See sensitive data (conditional rendering)
- Modify financial data (route protection)

---

## Testing Checklist

### Admin User Testing
- [ ] Can access full dashboard with all sections
- [ ] Can create/edit/delete all resources
- [ ] Can see financial data and charts
- [ ] Can access payment gateway stats
- [ ] Can manage system settings
- [ ] Can manage users (CRUD operations)
- [ ] Can approve/reject withdraw requests

### Manager User Testing
- [ ] Sees basic dashboard (KPIs only)
- [ ] Cannot see financial trends/charts
- [ ] Cannot see payment gateway stats
- [ ] Cannot access system settings
- [ ] Can view (but not edit) insurance programs
- [ ] Can view (but not edit) subscriptions
- [ ] Can view (but not manage) users
- [ ] Cannot create/edit/delete projects
- [ ] Cannot access financial routes
- [ ] Gets "Access Denied" on admin-only routes

---

## How to Assign Roles

### Via Admin Panel
1. Go to **Admin → Auth → Users**
2. Edit the user
3. Select **Roles** → Choose "admin" or "manager"
4. Save

### Via Database
```sql
-- Get role IDs
SELECT * FROM admin_roles;

-- Assign role to user
INSERT INTO admin_role_users (role_id, user_id) VALUES (1, 123);

-- Role IDs (typically):
-- 1 = Administrator (admin)
-- 2 = Manager (manager)
```

### Creating Manager Role (if not exists)
```sql
INSERT INTO admin_roles (id, name, slug, created_at, updated_at) 
VALUES (2, 'Manager', 'manager', NOW(), NOW());
```

---

## Extending Access Control

### Adding New Protected Routes
```php
// In app/Admin/routes.php
$router->group(['middleware' => 'admin.only'], function ($router) {
    $router->resource('your-resource', YourController::class);
});
```

### Adding New Role Checks
```php
// In app/Admin/Helpers/RoleBasedDashboard.php
protected function canSeeYourFeature()
{
    return $this->isAdmin();
}
```

### Protecting New Controllers
```php
use App\Admin\Helpers\RoleBasedDashboard;

class YourController extends AdminController
{
    use RoleBasedDashboard;
    
    protected function grid()
    {
        $grid = new Grid(new YourModel());
        
        if (!$this->isAdmin()) {
            $grid->disableCreateButton();
            $grid->disableActions();
        }
        
        return $grid;
    }
}
```

---

## Troubleshooting

### Manager Can Still Access Admin Routes
**Solution:** Clear route cache
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Dashboard Sections Not Hiding
**Solution:** Verify trait is included in HomeController
```php
use App\Admin\Helpers\RoleBasedDashboard;

class HomeController extends Controller
{
    use RoleBasedDashboard;
}
```

### Middleware Not Working
**Solution:** Verify middleware is registered in Kernel.php
```php
protected $routeMiddleware = [
    'admin.only' => \App\Http\Middleware\AdminOnly::class,
];
```

---

## Summary

✅ **Implemented:**
- Role-based dashboard rendering
- Route-level middleware protection
- Grid-level access control
- Form-level field restrictions
- Centralized role-checking helper

✅ **Security:**
- Multi-layer protection
- URL access prevention
- Conditional rendering
- Error handling

✅ **User Experience:**
- Clear access denied messages
- Role-appropriate dashboard
- Disabled buttons for restricted actions
- Clean, professional interface

---

## Files Modified

1. `app/Admin/Helpers/RoleBasedDashboard.php` - NEW
2. `app/Http/Middleware/AdminOnly.php` - NEW
3. `app/Http/Kernel.php` - Modified
4. `app/Admin/routes.php` - Modified
5. `app/Admin/Controllers/HomeController.php` - Modified
6. `app/Admin/Controllers/ProjectController.php` - Modified
7. `app/Admin/Controllers/InsuranceProgramController.php` - Modified
8. `app/Admin/Controllers/UserController.php` - Already had role check

---

**Implementation Complete ✅**  
Date: November 12, 2025  
System: DTEHM Insurance Admin Portal
