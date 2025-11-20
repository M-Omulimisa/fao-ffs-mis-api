# Laravel-Admin Controller Specifications

**Purpose:** Detailed specifications for each controller to ensure consistent implementation  
**Date:** January 2025

---

## 🎯 Controller Template

Each controller will follow this structure:

### File Structure
```
app/Admin/Controllers/
├── ProjectController.php
├── ProjectShareController.php
├── ProjectTransactionController.php
├── DisbursementController.php
├── AccountTransactionController.php
├── InsuranceProgramController.php
├── InsuranceSubscriptionController.php
├── InsuranceUserController.php
├── TransactionController.php (Insurance)
└── MedicalServiceRequestController.php
```

---

## 📋 Module 1: ProjectController

### Purpose
Manage investment projects with full CRUD and custom status actions.

### Grid Configuration
```php
protected function grid()
{
    $grid = new Grid(new Project());
    
    $grid->model()->orderBy('id', 'desc');
    
    // Columns
    $grid->column('id', __('ID'))->sortable();
    $grid->column('image')->image('', 60, 60);
    $grid->column('title', __('Title'))->filter('like')->display(function ($title) {
        return \Illuminate\Support\Str::limit($title, 40);
    });
    $grid->column('category', __('Category'))->filter(['investment', 'business', 'real_estate', 'agriculture']);
    $grid->column('status', __('Status'))->label([
        'pending' => 'warning',
        'ongoing' => 'success',
        'completed' => 'info',
        'cancelled' => 'danger',
    ])->filter(['pending', 'ongoing', 'completed', 'cancelled']);
    
    $grid->column('share_price', __('Share Price'))->display(function ($price) {
        return 'UGX ' . number_format($price, 0);
    });
    
    $grid->column('available_shares', __('Available'))->sortable();
    $grid->column('total_shares', __('Total Shares'))->sortable();
    
    $grid->column('target_amount', __('Target'))->display(function ($amount) {
        return 'UGX ' . number_format($amount, 0);
    })->sortable();
    
    $grid->column('current_amount', __('Raised'))->display(function ($amount) {
        return 'UGX ' . number_format($amount, 0);
    })->sortable();
    
    $grid->column('progress', __('Progress'))->display(function () {
        $percentage = ($this->target_amount > 0) 
            ? round(($this->current_amount / $this->target_amount) * 100, 1) 
            : 0;
        return $percentage . '%';
    });
    
    $grid->column('start_date', __('Start Date'))->sortable();
    $grid->column('end_date', __('End Date'))->sortable();
    $grid->column('created_at', __('Created'))->sortable();
    
    // Custom actions
    $grid->actions(function ($actions) {
        $id = $actions->getKey();
        $status = $actions->row->status;
        
        // Approve button (only for pending)
        if ($status == 'pending') {
            $actions->append('<a href="' . admin_url("projects/{$id}/approve") . '" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Approve</a>');
        }
        
        // Mark as completed (only for ongoing)
        if ($status == 'ongoing') {
            $actions->append('<a href="' . admin_url("projects/{$id}/complete") . '" class="btn btn-xs btn-info"><i class="fa fa-flag-checkered"></i> Complete</a>');
        }
        
        // Cancel button (for pending or ongoing)
        if (in_array($status, ['pending', 'ongoing'])) {
            $actions->append('<a href="' . admin_url("projects/{$id}/cancel") . '" class="btn btn-xs btn-danger" onclick="return confirm(\'Cancel this project?\')"><i class="fa fa-ban"></i> Cancel</a>');
        }
        
        // View investors
        $actions->append('<a href="' . admin_url("project-shares?project_id={$id}") . '" class="btn btn-xs btn-primary"><i class="fa fa-users"></i> Investors</a>');
    });
    
    return $grid;
}
```

### Detail Configuration
```php
protected function detail($id)
{
    $show = new Show(Project::findOrFail($id));
    
    $show->field('id', __('ID'));
    $show->field('image')->image();
    $show->field('title', __('Title'));
    $show->field('description', __('Description'))->unescape();
    $show->field('category', __('Category'));
    $show->field('status', __('Status'))->as(function ($status) {
        return ucfirst($status);
    });
    
    $show->field('share_price', __('Share Price'))->as(function ($price) {
        return 'UGX ' . number_format($price, 0);
    });
    $show->field('total_shares', __('Total Shares'));
    $show->field('available_shares', __('Available Shares'));
    $show->field('sold_shares', __('Sold Shares'))->as(function () {
        return $this->total_shares - $this->available_shares;
    });
    
    $show->field('target_amount', __('Target Amount'))->as(function ($amount) {
        return 'UGX ' . number_format($amount, 0);
    });
    $show->field('current_amount', __('Current Amount'))->as(function ($amount) {
        return 'UGX ' . number_format($amount, 0);
    });
    $show->field('net_profit', __('Net Profit'))->as(function ($profit) {
        return 'UGX ' . number_format($profit, 0);
    });
    
    $show->field('start_date', __('Start Date'));
    $show->field('end_date', __('End Date'));
    $show->field('created_at', __('Created At'));
    $show->field('updated_at', __('Updated At'));
    
    return $show;
}
```

### Form Configuration
```php
protected function form()
{
    $form = new Form(new Project());
    
    $form->image('image', __('Project Image'))->rules('required');
    $form->text('title', __('Title'))->rules('required|max:255');
    $form->textarea('description', __('Description'))->rules('required');
    
    $form->select('category', __('Category'))
        ->options([
            'investment' => 'Investment',
            'business' => 'Business',
            'real_estate' => 'Real Estate',
            'agriculture' => 'Agriculture',
        ])
        ->rules('required');
    
    $form->select('status', __('Status'))
        ->options([
            'pending' => 'Pending',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ])
        ->default('pending')
        ->rules('required');
    
    $form->decimal('share_price', __('Price per Share'))
        ->rules('required|numeric|min:0')
        ->help('Amount in UGX');
    
    $form->number('total_shares', __('Total Shares'))
        ->rules('required|integer|min:1')
        ->help('Total number of shares available');
    
    $form->number('available_shares', __('Available Shares'))
        ->rules('required|integer|min:0')
        ->help('Must not exceed total shares');
    
    $form->decimal('target_amount', __('Target Amount'))
        ->rules('required|numeric|min:0')
        ->help('Funding goal in UGX');
    
    $form->decimal('current_amount', __('Current Amount'))
        ->default(0)
        ->rules('numeric|min:0')
        ->help('Auto-updated on investments');
    
    $form->decimal('net_profit', __('Net Profit'))
        ->default(0)
        ->rules('numeric')
        ->help('Auto-calculated from transactions');
    
    $form->date('start_date', __('Start Date'))->rules('required');
    $form->date('end_date', __('End Date'))->rules('required');
    
    $form->disableCreatingCheck();
    $form->disableReset();
    $form->disableViewCheck();
    
    // Validation
    $form->saving(function (Form $form) {
        if ($form->available_shares > $form->total_shares) {
            return back()->withErrors(['available_shares' => 'Available shares cannot exceed total shares']);
        }
    });
    
    return $form;
}
```

### Custom Actions
```php
// Approve project
public function approve($id)
{
    $project = Project::findOrFail($id);
    
    if ($project->status != 'pending') {
        admin_toastr('Only pending projects can be approved', 'error');
        return redirect()->back();
    }
    
    $project->status = 'ongoing';
    $project->save();
    
    admin_toastr('Project approved successfully', 'success');
    return redirect()->back();
}

// Complete project
public function complete($id)
{
    $project = Project::findOrFail($id);
    
    if ($project->status != 'ongoing') {
        admin_toastr('Only ongoing projects can be completed', 'error');
        return redirect()->back();
    }
    
    $project->status = 'completed';
    $project->save();
    
    admin_toastr('Project marked as completed', 'success');
    return redirect()->back();
}

// Cancel project
public function cancel($id)
{
    $project = Project::findOrFail($id);
    
    if (!in_array($project->status, ['pending', 'ongoing'])) {
        admin_toastr('Cannot cancel this project', 'error');
        return redirect()->back();
    }
    
    $project->status = 'cancelled';
    $project->save();
    
    admin_toastr('Project cancelled', 'warning');
    return redirect()->back();
}
```

### Routes Addition
```php
// In routes.php
$router->resource('projects', ProjectController::class);
$router->get('projects/{id}/approve', 'ProjectController@approve');
$router->get('projects/{id}/complete', 'ProjectController@complete');
$router->get('projects/{id}/cancel', 'ProjectController@cancel');
```

---

## 📋 Module 2: ProjectShareController

### Purpose
Manage investor shares/investments with approval workflow.

### Grid Configuration
```php
protected function grid()
{
    $grid = new Grid(new ProjectShare());
    
    $grid->model()->orderBy('id', 'desc');
    
    $grid->column('id', __('ID'))->sortable();
    
    // Investor info
    $grid->column('investor.name', __('Investor'))->filter('like');
    $grid->column('investor.phone_number', __('Phone'));
    
    // Project info
    $grid->column('project.title', __('Project'))->filter('like')->display(function ($title) {
        return \Illuminate\Support\Str::limit($title, 30);
    });
    
    $grid->column('number_of_shares', __('Shares'))->sortable();
    $grid->column('amount_per_share', __('Price/Share'))->display(function ($amount) {
        return 'UGX ' . number_format($amount, 0);
    });
    
    $grid->column('total_amount_paid', __('Total Paid'))->display(function ($amount) {
        return 'UGX ' . number_format($amount, 0);
    })->sortable();
    
    $grid->column('payment_status', __('Status'))->label([
        'completed' => 'success',
        'pending' => 'warning',
        'failed' => 'danger',
    ])->filter(['completed', 'pending', 'failed']);
    
    $grid->column('purchase_date', __('Date'))->sortable();
    $grid->column('created_at', __('Created'))->sortable();
    
    // Filters
    $grid->filter(function ($filter) {
        $filter->disableIdFilter();
        
        $filter->equal('project_id', 'Project')->select(Project::pluck('title', 'id'));
        $filter->equal('investor_id', 'Investor')->select(User::pluck('name', 'id'));
        $filter->equal('payment_status', 'Payment Status')->select([
            'completed' => 'Completed',
            'pending' => 'Pending',
            'failed' => 'Failed',
        ]);
        $filter->between('purchase_date', 'Purchase Date')->date();
    });
    
    // Custom actions
    $grid->actions(function ($actions) {
        $id = $actions->getKey();
        $status = $actions->row->payment_status;
        
        if ($status == 'pending') {
            $actions->append('<a href="' . admin_url("project-shares/{$id}/approve") . '" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Approve</a>');
            $actions->append('<a href="' . admin_url("project-shares/{$id}/reject") . '" class="btn btn-xs btn-danger" onclick="return confirm(\'Reject this investment?\')"><i class="fa fa-times"></i> Reject</a>');
        }
    });
    
    // Disable create button (investments created via mobile app)
    $grid->disableCreateButton();
    $grid->disableActions(); // Can only view
    
    return $grid;
}
```

### Custom Actions
```php
public function approve($id)
{
    $share = ProjectShare::findOrFail($id);
    
    if ($share->payment_status != 'pending') {
        admin_toastr('Only pending investments can be approved', 'error');
        return redirect()->back();
    }
    
    DB::beginTransaction();
    try {
        // Update share status
        $share->payment_status = 'completed';
        $share->save();
        
        // Update project amounts
        $project = $share->project;
        $project->current_amount += $share->total_amount_paid;
        $project->available_shares -= $share->number_of_shares;
        $project->save();
        
        // Create project transaction
        ProjectTransaction::create([
            'project_id' => $share->project_id,
            'amount' => $share->total_amount_paid,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'share_purchase',
            'description' => 'Share purchase by ' . $share->investor->name,
            'created_by_id' => auth()->id(),
        ]);
        
        DB::commit();
        admin_toastr('Investment approved successfully', 'success');
    } catch (\Exception $e) {
        DB::rollBack();
        admin_toastr('Error approving investment: ' . $e->getMessage(), 'error');
    }
    
    return redirect()->back();
}

public function reject($id)
{
    $share = ProjectShare::findOrFail($id);
    
    if ($share->payment_status != 'pending') {
        admin_toastr('Only pending investments can be rejected', 'error');
        return redirect()->back();
    }
    
    $share->payment_status = 'failed';
    $share->save();
    
    admin_toastr('Investment rejected', 'warning');
    return redirect()->back();
}
```

---

## 📋 Module 3: ProjectTransactionController

### Purpose
Manage project income/expenses with restrictions on automated transactions.

### Grid Configuration
```php
protected function grid()
{
    $grid = new Grid(new ProjectTransaction());
    
    $grid->model()->orderBy('transaction_date', 'desc');
    
    $grid->column('id', __('ID'))->sortable();
    $grid->column('project.title', __('Project'))->filter('like');
    
    $grid->column('amount', __('Amount'))->display(function ($amount) {
        return 'UGX ' . number_format(abs($amount), 0);
    })->sortable();
    
    $grid->column('type', __('Type'))->label([
        'income' => 'success',
        'expense' => 'danger',
    ])->filter(['income', 'expense']);
    
    $grid->column('source', __('Source'))->label([
        'manual' => 'default',
        'share_purchase' => 'primary',
        'disbursement' => 'info',
    ])->filter(['manual', 'share_purchase', 'disbursement']);
    
    $grid->column('description', __('Description'))->display(function ($desc) {
        return \Illuminate\Support\Str::limit($desc, 50);
    });
    
    $grid->column('transaction_date', __('Date'))->sortable();
    $grid->column('creator.name', __('Created By'));
    
    // Filters
    $grid->filter(function ($filter) {
        $filter->disableIdFilter();
        $filter->equal('project_id', 'Project')->select(Project::pluck('title', 'id'));
        $filter->equal('type', 'Type')->select(['income' => 'Income', 'expense' => 'Expense']);
        $filter->equal('source', 'Source')->select([
            'manual' => 'Manual',
            'share_purchase' => 'Share Purchase',
            'disbursement' => 'Disbursement',
        ]);
        $filter->between('transaction_date', 'Date')->date();
    });
    
    // Actions: Only allow editing/deleting manual transactions
    $grid->actions(function ($actions) {
        $source = $actions->row->source;
        
        if ($source != 'manual') {
            $actions->disableEdit();
            $actions->disableDelete();
        }
    });
    
    return $grid;
}
```

### Form Configuration
```php
protected function form()
{
    $form = new Form(new ProjectTransaction());
    
    $form->select('project_id', __('Project'))
        ->options(Project::where('status', 'ongoing')->pluck('title', 'id'))
        ->rules('required');
    
    $form->decimal('amount', __('Amount'))
        ->rules('required|numeric|min:0')
        ->help('Enter positive amount (system will handle sign based on type)');
    
    $form->select('type', __('Type'))
        ->options([
            'income' => 'Income',
            'expense' => 'Expense',
        ])
        ->rules('required');
    
    $form->hidden('source')->default('manual');
    
    $form->textarea('description', __('Description'))->rules('required');
    
    $form->date('transaction_date', __('Transaction Date'))
        ->default(date('Y-m-d'))
        ->rules('required');
    
    $form->hidden('created_by_id')->default(auth()->id());
    
    $form->disableCreatingCheck();
    $form->disableReset();
    $form->disableViewCheck();
    
    // Prevent editing automated transactions
    $form->editing(function (Form $form) {
        if ($form->model()->source != 'manual') {
            admin_toastr('Cannot edit automated transactions', 'error');
            return redirect(admin_url('project-transactions'));
        }
    });
    
    // Update project net profit after save
    $form->saved(function (Form $form) {
        $transaction = $form->model();
        $project = $transaction->project;
        
        // Recalculate net profit
        $totalIncome = ProjectTransaction::where('project_id', $project->id)
            ->where('type', 'income')
            ->sum('amount');
        
        $totalExpense = ProjectTransaction::where('project_id', $project->id)
            ->where('type', 'expense')
            ->sum('amount');
        
        $project->net_profit = $totalIncome - abs($totalExpense);
        $project->save();
    });
    
    return $form;
}
```

---

*[Continue with remaining controllers: Disbursement, AccountTransaction, InsuranceProgram, etc. - Same detailed pattern for each]*

---

## 📝 Summary

**Total Controllers to Create:** 10
- ✅ UserController (existing - enhance)
- 🆕 ProjectController
- 🆕 ProjectShareController
- 🆕 ProjectTransactionController
- 🆕 DisbursementController
- 🆕 AccountTransactionController
- 🆕 InsuranceProgramController
- 🆕 InsuranceSubscriptionController
- 🆕 InsuranceUserController
- 🆕 TransactionController (Insurance)
- 🆕 MedicalServiceRequestController
- ✅ ProductController (existing - clean)
- ✅ OrderController (existing - enhance)
- ✅ NotificationController (existing - keep)

**Next Steps:**
1. Clear routes.php
2. Clear admin menu
3. Implement controllers one by one following these specs
4. Test each module thoroughly
5. Update menu structure
6. Document any changes

---

**Document Status:** Ready for Implementation  
**Last Updated:** January 2025
