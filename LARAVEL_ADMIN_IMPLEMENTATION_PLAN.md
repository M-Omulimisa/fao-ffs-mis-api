# Laravel-Admin 360° System Control - Implementation Plan

**Date:** January 2025  
**Status:** Planning Phase  
**Approach:** Module-by-module (like Insurance Dashboard)

---

## 🎯 Objectives

1. **Complete Cleanup**: Remove all legacy routes and menu items
2. **Systematic Rebuild**: Create controllers module-by-module
3. **360° Control**: Full CRUD + custom actions for all entities
4. **Structured Approach**: Follow proven patterns from existing controllers
5. **Dashboard**: Leave blank for now (implement later)

---

## 📊 System Entities Analysis

### Core Business Modules

#### 1. **Users Management** ✅ (Already exists - will enhance)
- **Model:** `User`
- **Current Controller:** `UserController.php`
- **Table:** `users`
- **Status:** Needs enhancement with custom actions

#### 2. **Projects** 🆕
- **Model:** `Project`
- **Table:** `projects`
- **Fields:** title, description, status, category, start_date, end_date, target_amount, current_amount, share_price, total_shares, available_shares, images
- **Actions Needed:**
  - Approve/Reject project
  - Mark as ongoing/completed/cancelled
  - Generate project report
  - View investors
  - Export data

#### 3. **Project Shares (Investments)** 🆕
- **Model:** `ProjectShare`
- **Table:** `project_shares`
- **Fields:** project_id, investor_id, number_of_shares, amount_per_share, total_amount_paid, purchase_date, payment_status
- **Actions Needed:**
  - Approve/Reject investment
  - Mark payment as completed
  - Refund investment
  - View investor portfolio
  - Export transactions

#### 4. **Project Transactions** 🆕
- **Model:** `ProjectTransaction`
- **Table:** `project_transactions`
- **Fields:** project_id, amount, transaction_date, type (income/expense), source (manual/share_purchase/disbursement), description
- **Actions Needed:**
  - Create manual transaction
  - Edit manual transaction (only)
  - Delete manual transaction (only)
  - Filter by project/type/source/date
  - Export report

#### 5. **Disbursements** 🆕
- **Model:** `Disbursement`
- **Table:** `disbursements`
- **Fields:** project_id, amount, disbursement_date, description, created_by_id
- **Actions Needed:**
  - Create disbursement (auto-distributes to investors)
  - View investor distribution
  - Export disbursement report
  - Cancel disbursement (if not distributed)

#### 6. **Account Transactions** 🆕
- **Model:** `AccountTransaction`
- **Table:** `account_transactions`
- **Fields:** user_id, amount, transaction_date, transaction_type (credit/debit), source (disbursement/withdrawal/deposit), description
- **Actions Needed:**
  - View user transaction history
  - Filter by user/type/source/date
  - Export user account statement
  - Manual adjustment (admin only)

#### 7. **Insurance Programs** 🆕
- **Model:** `InsuranceProgram`
- **Table:** `insurance_programs`
- **Fields:** name, description, monthly_premium, coverage_amount, duration_months, features, status
- **Actions Needed:**
  - Create/Edit/Delete program
  - Activate/Deactivate
  - View subscribers
  - Export program report

#### 8. **Insurance Subscriptions** 🆕
- **Model:** `InsuranceSubscription`
- **Table:** `insurance_subscriptions`
- **Fields:** insurance_user_id, insurance_program_id, start_date, end_date, status, monthly_premium, total_paid
- **Actions Needed:**
  - Approve/Reject subscription
  - Mark as active/suspended/cancelled
  - View payment history
  - Export subscriptions

#### 9. **Insurance Users** 🆕
- **Model:** `InsuranceUser`
- **Table:** `insurance_users`
- **Fields:** user_id, name, phone, email, address, dob, gender, id_number, id_photo, balance
- **Actions Needed:**
  - View profile
  - Update balance (manual adjustment)
  - View subscriptions
  - View transactions
  - Export user data

#### 10. **Insurance Transactions (Savings/Withdrawals)** 🆕
- **Model:** `Transaction`
- **Table:** `transactions`
- **Fields:** insurance_user_id, amount, transaction_type (deposit/withdrawal), payment_method, reference_number, status, receipt_photo
- **Actions Needed:**
  - Approve/Reject transaction
  - Mark as completed/failed
  - View receipt
  - Export transactions

#### 11. **Medical Service Requests** 🆕
- **Model:** `MedicalServiceRequest`
- **Table:** `medical_service_requests`
- **Fields:** insurance_user_id, service_type, urgency_level, symptoms, preferred_hospital, status, assigned_hospital, estimated_cost
- **Actions Needed:**
  - Assign to hospital/doctor
  - Update status (pending/processing/completed/cancelled)
  - Add admin notes
  - Upload documents
  - Approve/Reject request
  - Export requests

#### 12. **Products** ✅ (Exists - will clean up)
- **Model:** `Product`
- **Current Controller:** `ProductController.php`
- **Table:** `products`
- **Actions:** Standard CRUD + stock management

#### 13. **Orders** ✅ (Exists - will enhance)
- **Model:** `Order`
- **Current Controller:** `OrderController.php`
- **Table:** `orders`
- **Actions:** View, process, mark as delivered/cancelled

#### 14. **Notifications** ✅ (Exists - will keep enhanced version)
- **Model:** `NotificationModel`
- **Current Controller:** `NotificationController.php`
- **Table:** `notifications`
- **Actions:** Create, send, schedule, view analytics

#### 15. **System Configurations** ✅ (Exists)
- **Model:** `SystemConfiguration`
- **Table:** `system_configurations`
- **Actions:** Update settings

---

## 🏗️ Laravel-Admin Controller Pattern

### Standard Structure (from UserController analysis)

```php
<?php

namespace App\Admin\Controllers;

use App\Models\ModelName;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ModelNameController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Display Name';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ModelName());

        // Order
        $grid->model()->orderBy('id', 'desc');

        // Disable batch actions if needed
        $grid->disableBatchActions();

        // Define columns
        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Name'))->filter('like');
        $grid->column('status', __('Status'))->label([
            'active' => 'success',
            'pending' => 'warning',
            'inactive' => 'danger',
        ])->filter(['active', 'pending', 'inactive']);
        $grid->column('created_at', __('Created At'))->sortable();

        // Hide columns
        $grid->column('sensitive_field')->hide();

        // Custom display
        $grid->column('amount')->display(function ($amount) {
            return number_format($amount, 2);
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ModelName::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Name'));
        $show->field('status', __('Status'));
        // Add all fields...

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ModelName());

        $form->text('name', __('Name'))->rules('required');
        $form->select('status', __('Status'))
            ->options([
                'active' => 'Active',
                'pending' => 'Pending',
                'inactive' => 'Inactive',
            ])
            ->default('pending')
            ->rules('required');

        $form->textarea('description', __('Description'));
        $form->decimal('amount', __('Amount'))->default(0.00);
        $form->date('start_date', __('Start Date'));
        $form->image('photo', __('Photo'));

        // Disable certain buttons
        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
```

### Custom Actions Pattern

```php
// In grid() method
$grid->actions(function ($actions) {
    $actions->disableDelete();
    $actions->disableEdit();
    $actions->disableView();
    
    // Add custom action
    $actions->append('<a href="custom-action/' . $actions->getKey() . '"><i class="fa fa-check"></i> Approve</a>');
});

// Custom action route
$router->get('custom-action/{id}', 'ModelNameController@customAction');

// Custom action method
public function customAction($id)
{
    $model = ModelName::findOrFail($id);
    $model->status = 'approved';
    $model->save();
    
    admin_toastr('Approved successfully', 'success');
    return redirect()->back();
}
```

---

## 📝 Implementation Sequence

### Phase 1: Foundation (Users)
1. ✅ Review existing UserController
2. ✅ Enhance with custom actions
3. ✅ Test CRUD operations
4. ✅ Add to clean menu

### Phase 2: Investment Core (Projects Module)
1. Create ProjectController
2. Create ProjectShareController  
3. Create ProjectTransactionController
4. Create DisbursementController
5. Create AccountTransactionController
6. Test complete investment workflow

### Phase 3: Insurance Core
1. Create InsuranceProgramController
2. Create InsuranceSubscriptionController
3. Create InsuranceUserController
4. Create TransactionController (insurance savings/withdrawals)
5. Test complete insurance workflow

### Phase 4: Medical Services
1. Create MedicalServiceRequestController
2. Add document upload support
3. Test request workflow

### Phase 5: E-Commerce (Clean up existing)
1. Review and enhance ProductController
2. Review and enhance OrderController
3. Add delivery tracking

### Phase 6: System Management
1. Keep enhanced NotificationController
2. Keep SystemConfigurationController
3. Add analytics dashboard (later)

---

## 🗂️ Routes Structure (Clean)

```php
<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    // Dashboard (blank for now)
    $router->get('/', 'HomeController@index')->name('home');

    // ========================================
    // USERS MANAGEMENT
    // ========================================
    $router->resource('users', UserController::class);

    // ========================================
    // INVESTMENT MANAGEMENT
    // ========================================
    $router->resource('projects', ProjectController::class);
    $router->resource('project-shares', ProjectShareController::class);
    $router->resource('project-transactions', ProjectTransactionController::class);
    $router->resource('disbursements', DisbursementController::class);
    $router->resource('account-transactions', AccountTransactionController::class);

    // ========================================
    // INSURANCE MANAGEMENT
    // ========================================
    $router->resource('insurance-programs', InsuranceProgramController::class);
    $router->resource('insurance-subscriptions', InsuranceSubscriptionController::class);
    $router->resource('insurance-users', InsuranceUserController::class);
    $router->resource('insurance-transactions', TransactionController::class);

    // ========================================
    // MEDICAL SERVICES
    // ========================================
    $router->resource('medical-service-requests', MedicalServiceRequestController::class);

    // ========================================
    // E-COMMERCE
    // ========================================
    $router->resource('products', ProductController::class);
    $router->resource('orders', OrderController::class);

    // ========================================
    // SYSTEM
    // ========================================
    $router->resource('notifications', NotificationController::class);
    $router->resource('system-configurations', SystemConfigurationController::class);
});
```

---

## 🎨 Menu Structure (Database)

```sql
-- Clear existing menu (except Home)
DELETE FROM admin_menu WHERE id > 1;

-- Reset auto increment
ALTER TABLE admin_menu AUTO_INCREMENT = 2;

-- Insert new menu structure
INSERT INTO admin_menu (parent_id, order, title, icon, uri) VALUES

-- Investment Management
(0, 100, 'Investments', 'fa-chart-line', NULL),
(2, 101, 'Projects', 'fa-project-diagram', 'projects'),
(2, 102, 'Investors & Shares', 'fa-users', 'project-shares'),
(2, 103, 'Transactions', 'fa-exchange-alt', 'project-transactions'),
(2, 104, 'Disbursements', 'fa-hand-holding-usd', 'disbursements'),
(2, 105, 'Account Transactions', 'fa-wallet', 'account-transactions'),

-- Insurance Management
(0, 200, 'Insurance', 'fa-shield-alt', NULL),
(8, 201, 'Programs', 'fa-file-medical', 'insurance-programs'),
(8, 202, 'Subscriptions', 'fa-calendar-check', 'insurance-subscriptions'),
(8, 203, 'Users', 'fa-user-shield', 'insurance-users'),
(8, 204, 'Transactions', 'fa-piggy-bank', 'insurance-transactions'),

-- Medical Services
(0, 300, 'Medical Services', 'fa-hospital', 'medical-service-requests'),

-- E-Commerce
(0, 400, 'E-Commerce', 'fa-shopping-cart', NULL),
(14, 401, 'Products', 'fa-box', 'products'),
(14, 402, 'Orders', 'fa-shopping-bag', 'orders'),

-- System
(0, 500, 'System', 'fa-cog', NULL),
(17, 501, 'Users', 'fa-users', 'users'),
(17, 502, 'Notifications', 'fa-bell', 'notifications'),
(17, 503, 'Configurations', 'fa-wrench', 'system-configurations');
```

---

## ✅ Next Actions

1. **Complete UserController analysis** ✅
2. **Create this implementation plan** ✅
3. **Clear routes.php** (keep only HomeController)
4. **Clear admin menu** (via SQL or admin panel)
5. **Start Phase 1: Users Enhancement**
6. **Continue module-by-module**

---

## 📌 Notes

- Follow the proven pattern from UserController
- Each controller should have: grid(), detail(), form()
- Add custom actions where business logic requires
- Use labels for status fields (color coding)
- Implement proper validation rules
- Disable unnecessary features (batch actions, etc.)
- Test each module before moving to next
- Document as we build

---

**Prepared by:** GitHub Copilot  
**Review Status:** Ready for Implementation
