<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Hash;

class UserController extends AdminController
{
    use IpScopeable;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Users';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->model()->orderBy('id', 'desc');

        // IP Scoping: IP admins see only their own users
        $this->applyIpScope($grid);

        $grid->disableBatchActions();

        // ID Column
        $grid->column('id', __('ID'))
            ->sortable()
            ->width(60)
            ->style('font-weight: bold; color: #05179F;');

        $grid->column('avatar', __('Photo'))
            ->lightbox(['width' => 50, 'height' => 50])
            ->width(60);

        // Full Name Column
        $grid->column('full_name', __('Full Name'))
            ->display(function () {
                return trim($this->first_name . ' ' . $this->last_name);
            })
            ->sortable()
            ->width(180);

        // Gender Column
        $grid->column('sex', __('Gender'))
            ->label([
                'Male' => 'info',
                'Female' => 'danger',
            ])
            ->width(80);

        // Phone Number Column
        $grid->column('phone_number', __('Phone'))
            ->sortable()
            ->width(120);

        // Email Column
        $grid->column('email', __('Email'))
            ->sortable()
            ->hide()
            ->width(180);

        // User Type Column
        $grid->column('user_type', __('User Type'))
            ->label([
                'Admin' => 'danger',
                'Customer' => 'success',
                'Vendor' => 'warning',
            ])
            ->hide()
            ->filter([
                'Admin' => 'Admin',
                'Customer' => 'Customer',
                'Vendor' => 'Vendor',
            ])
            ->sortable()
            ->width(100);

        // Country Column
        $grid->column('country', __('Country'))
            ->hide()
            ->width(120);

        // Tribe Column
        $grid->column('tribe', __('Tribe'))
            ->width(120);

        // Address Column
        $grid->column('address', __('Address'))
            ->limit(30)
            ->width(150);

        // Status Column
        $grid->column('status', __('Status'))
            ->label([
                'Active' => 'success',
                'Pending' => 'warning',
                'Banned' => 'danger',
                'Inactive' => 'default',
            ], 'Active')

            ->filter([
                'Active' => 'Active',
                'Pending' => 'Pending',
                'Banned' => 'Banned',
                'Inactive' => 'Inactive',
            ])
            ->width(90);

        // Date of Birth Column
        $grid->column('dob', __('DOB'))
            ->display(function ($dob) {
                if (empty($dob) || $dob == '0000-00-00') {
                    return '-';
                }
                return date('d M Y', strtotime($dob));
            })
            ->hide()
            ->width(100);

        // Created At Column
        $grid->column('created_at', __('Registered'))
            ->display(function ($created_at) {
                return date('d M Y', strtotime($created_at));
            })
            ->sortable()
            ->width(100);

        // Quick Search
        $grid->quickSearch('first_name', 'last_name', 'email', 'phone_number')
            ->placeholder('Search by name, email, or phone');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('email', 'Email');

            $filter->equal('sex', 'Gender')->radio([
                '' => 'All',
                'Male' => 'Male',
                'Female' => 'Female',
            ]);

            $filter->equal('status', 'Status')->select([
                'Active' => 'Active',
                'Pending' => 'Pending',
                'Inactive' => 'Inactive',
                'Banned' => 'Banned',
            ]);

            $filter->like('country', 'Country');
            $filter->like('tribe', 'Tribe');
            $filter->between('created_at', 'Registered Date')->date();
        });


        // SMS action column
        $grid->column('sms_actions', 'SMS Actions')->display(function () {
            $userId = $this->id;
            return '
                <a href="' . url('/admin/users/' . $userId . '/send-credentials') . '" 
                   target="_blank" 
                   class="btn btn-xs btn-success" 
                   title="Send login credentials via SMS">
                    <i class="fa fa-paper-plane"></i> Credentials
                </a>
                <br>
                <a href="' . url('/admin/users/' . $userId . '/send-welcome') . '" 
                   target="_blank" 
                   class="btn btn-xs btn-info" 
                   title="Send welcome message via SMS"
                   style="margin-left: 3px; margin-top 10px;">
                    <i class="fa fa-envelope"></i> Welcome
                </a>
            ';
        })->width(200);

        // Disable view action since we don't use it
        $grid->actions(function ($actions) {
            $actions->disableView();
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
        // Prevent detail view from being accessed during creation
        if ($id === 'create' || !is_numeric($id)) {
            abort(404);
        }

        $show = new Show(User::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('first_name', __('First Name'));
        $show->field('last_name', __('Last Name'));
        $show->field('username', __('Username'));
        $show->field('email', __('Email'));
        $show->field('phone_number', __('Phone Number'));
        $show->field('sex', __('Gender'));
        $show->field('dob', __('Date of Birth'));
        $show->field('user_type', __('User Type'));
        $show->field('status', __('Status'));

        $show->divider();

        $show->field('country', __('Country'));
        $show->field('tribe', __('Tribe'));
        $show->field('address', __('Address'));
        $show->field('occupation', __('Occupation'));

        $show->divider();

        $show->field('father_name', __("Father's Name"));
        $show->field('mother_name', __("Mother's Name"));

        $show->divider();

        $show->field('avatar', __('Photo'));
        $show->field('reg_date', __('Registration Date'));
        $show->field('last_seen', __('Last Seen'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());

        if ($form->isCreating()) {
            // SIMPLIFIED FORM FOR USER CREATION - Only Essential Info
            $form->hidden('user_type')->value('Customer');
            
            $form->divider('Basic Information');
            
            $form->row(function ($row) {
                $row->width(6)->text('first_name', __('First Name'))
                    ->rules('required')
                    ->help('Required field');
                $row->width(6)->text('last_name', __('Last Name'))
                    ->rules('required')
                    ->help('Required field');
            });

            $form->row(function ($row) {
                $row->width(6)->text('phone_number', __('Phone Number'))
                    ->rules('required|unique:users,phone_number')
                    ->help('Required field. Will be used as username.');
                    
                $row->width(6)->radio('sex', __('Gender'))
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ])
                    ->rules('required')
                    ->default('Male');
            });

            $form->divider('Organization');
            
            $form->row(function ($row) {
                $ipOptions = ImplementingPartner::where('status', 'Active')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
                $row->width(6)->select('ip_id', __('Implementing Partner'))
                    ->options($ipOptions)
                    ->help('Select the Implementing Partner this user belongs to');

                // Role assignment - IP admins can only assign IP-level and below roles
                $roleModel = config('admin.database.roles_model');
                $rolesQuery = $roleModel::query();
                if (!$this->isSuperAdmin()) {
                    $rolesQuery->where('slug', '!=', 'super_admin');
                }
                $row->width(6)->multipleSelect('roles', trans('admin.roles'))
                    ->options($rolesQuery->pluck('name', 'id'))
                    ->help('Assign roles to this user (optional)');
            });

            $form->divider('Security');

            $form->row(function ($row) {
                $row->width(6)->password('password', __('Password'))
                    ->help('Optional. If blank, phone number will be used as default password.');
                $row->width(6)->password('password_confirmation', __('Confirm Password'))
                    ->help('Re-enter password for confirmation');
            });

            $form->html('<div class="alert alert-info">
                <strong>Note:</strong> 
                <ul>
                    <li>Username will be automatically set to the phone number</li>
                    <li>User will be registered under your admin account</li>
                    <li>If no password is set, it defaults to the phone number (user can change later)</li>
                </ul>
            </div>');
            
            return $form;
        }

        // FULL FORM FOR EDITING EXISTING USERS
        $form->row(function ($row) {
            $row->width(3)->text('first_name', __('First Name'))
                ->help('First name');
            $row->width(3)->text('last_name', __('Last Name'))
                ->help('Last name');
            $row->width(3)->radio('sex', __('Gender'))
                ->options([
                    'Male' => 'Male',
                    'Female' => 'Female',
                ])
                ->default('Male');

            $row->width(3)->text('phone_number', __('Phone Number'))
                ->help('Phone number');
        });

        $form->divider('Organization');

        $form->row(function ($row) {
            $ipOptions = ImplementingPartner::where('status', 'Active')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
            $row->width(6)->select('ip_id', __('Implementing Partner'))
                ->options($ipOptions)
                ->help('Select the Implementing Partner this user belongs to');

            $row->width(3)->image('avatar', __('Profile Photo'))
                ->help('Upload profile photo (optional)')
                ->uniqueName()
                ->move('images/users');

            // Role assignment - IP admins can only assign IP-level and below roles (not Super Admin)
            $roleModel = config('admin.database.roles_model');
            $rolesQuery = $roleModel::query();
            if (!$this->isSuperAdmin()) {
                // IP admins cannot assign Super Admin role (slug: super_admin)
                $rolesQuery->where('slug', '!=', 'super_admin');
            }
            $row->width(3)->multipleSelect('roles', trans('admin.roles'))
                ->options($rolesQuery->pluck('name', 'id'));
        });

        $form->row(function ($row) {
            $row->width(4)->radio('user_type', __('User Type'))
                ->options([
                    'Customer' => 'Customer',
                    'Admin' => 'Admin',
                ])
                ->default('Customer')
                ->help('Customer = Regular User, Admin = System Administrator');

            $row->width(4)->date('dob', __('Date of Birth'))
                ->format('YYYY-MM-DD')
                ->help('Optional field');

            $row->width(4)->text('email', __('Email'))
                ->help('Optional');
        });

        // SECTION: Location Information
        $form->divider('Location Information');

        $countries = [
            'Uganda' => 'Uganda',
            'Kenya' => 'Kenya',
            'Tanzania' => 'Tanzania',
            'Rwanda' => 'Rwanda',
            'Burundi' => 'Burundi',
            'South Sudan' => 'South Sudan',
            'DRC' => 'DRC',
        ];

        $tribes = [
            'Acholi' => 'Acholi',
            'Alur' => 'Alur',
            'Baganda' => 'Baganda',
            'Bagisu' => 'Bagisu',
            'Bagwere' => 'Bagwere',
            'Banyankole' => 'Banyankole',
            'Banyoro' => 'Banyoro',
            'Bakonzo' => 'Bakonzo',
            'Basoga' => 'Basoga',
            'Batoro' => 'Batoro',
            'Iteso' => 'Iteso',
            'Japadhola' => 'Japadhola',
            'Kakwa' => 'Kakwa',
            'Karamojong' => 'Karamojong',
            'Langi' => 'Langi',
            'Lugbara' => 'Lugbara',
            'Madi' => 'Madi',
            'Other' => 'Other',
        ];

        $form->row(function ($row) use ($countries, $tribes) {
            $row->width(6)->select('country', __('Country of Residence'))
                ->options($countries)
                ->default('Uganda')
                ->help('Optional - defaults to Uganda');

            $row->width(6)->radio('tribe', __('Tribe'))
                ->options($tribes)
                ->help('Optional - select your tribe');
        });

        $form->row(function ($row) {
            $row->width(6)->text('address', __('Home Address'))
                ->rules('required')
                ->help('Required field. Your permanent home address');

            $row->width(6)->text('occupation', __('Occupation'))
                ->help('Optional');
        });

        // SECTION: Family Information
        $form->divider('Family Information');

        $form->row(function ($row) {
            $row->width(6)->text('father_name', __("Father's Name"))
                ->rules('required')
                ->help('Required field');

            $row->width(6)->text('mother_name', __("Mother's Name"))
                ->rules('required')
                ->help('Required field');
        });

        // SECTION 5: Biological Children (Optional)
        $form->divider('Biological Children (if any)');

        $form->row(function ($row) {
            $row->width(6)->text('child_1', __('1st Child'))
                ->help('Full name of 1st child (optional)');

            $row->width(6)->text('child_2', __('2nd Child'))
                ->help('Full name of 2nd child (optional)');
        });

        $form->row(function ($row) {
            $row->width(6)->text('child_3', __('3rd Child'))
                ->help('Full name of 3rd child (optional)');

            $row->width(6)->text('child_4', __('4th Child'))
                ->help('Full name of 4th child (optional)');
        });


        // SECTION 8: Account Status & Password
        $form->divider('Account Status & Security');

        $form->row(function ($row) {
            $row->width(4)->radio('status', __('Account Status'))
                ->options([
                    'Active' => 'Active',
                    'Pending' => 'Pending',
                    'Inactive' => 'Inactive',
                    'Banned' => 'Banned',
                ])
                ->default('Active');

            $row->width(4)->password('password', __('Password'))
                ->help('Leave blank to keep current password. Minimum 6 characters.');

            $row->width(4)->password('password_confirmation', __('Confirm Password'))
                ->help('Re-enter password for confirmation');
        });

        // Auto-generate name field from first_name and last_name
        $form->saving(function (Form $form) {
            // Auto-generate full name from first_name and last_name
            if ($form->first_name && $form->last_name) {
                $form->name = trim($form->first_name . ' ' . $form->last_name);
            }

            // FOR NEW USERS: Auto-fill required fields
            if ($form->isCreating()) {
                // Set username to phone_number
                if ($form->phone_number) {
                    $form->username = $form->phone_number;
                }

                // Set default password to phone_number if no password provided
                if (!$form->password) {
                    $form->password = Hash::make($form->phone_number);
                } else {
                    $form->password = Hash::make($form->password);
                }

                // Set registered_by_id to current admin
                $form->registered_by_id = \Admin::user()->id;

                // Set default values for required fields
                if (!$form->user_type) {
                    $form->user_type = 'Customer';
                }

                if (!$form->status) {
                    $form->status = 'Active';
                }

                // Set default country
                if (!$form->country) {
                    $form->country = 'Uganda';
                }
            }

            // Hash password if provided (for updates)
            if ($form->password && !$form->isCreating()) {
                if ($form->model()->password != $form->password) {
                    $form->password = Hash::make($form->password);
                }
            }
        });

        // Hide password confirmation from database
        $form->ignore(['password_confirmation']);

        // Form configuration
        // $form->disableCreatingCheck();
        // $form->disableEditingCheck();
        $form->disableViewCheck();

        // Tools configuration
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
