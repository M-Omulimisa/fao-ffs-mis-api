<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\DtehmMembership;
use App\Models\MembershipPayment;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class UserController extends AdminController
{
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
        $grid->quickSearch('first_name', 'last_name', 'email', 'phone_number', 'business_name', 'sponsor_id')->placeholder('Search by name, email, phone, or DIP ID');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('email', 'Email');
            $filter->like('business_name', 'DIP ID');
            $filter->like('dtehm_member_id', 'DTEHM ID');
            $filter->like('sponsor_id', 'Sponsor ID');

            $filter->equal('sex', 'Gender')->radio([
                '' => 'All',
                'Male' => 'Male',
                'Female' => 'Female',
            ]);

            $filter->equal('is_dtehm_member', 'DTEHM Member')->radio([
                '' => 'All',
                'Yes' => 'Yes',
                'No' => 'No',
            ]);

            $filter->equal('is_dip_member', 'DIP Member')->radio([
                '' => 'All',
                'Yes' => 'Yes',
                'No' => 'No',
            ]);

            $filter->equal('dtehm_membership_is_paid', 'DTEHM Paid')->radio([
                '' => 'All',
                'Yes' => 'Paid',
                'No' => 'Unpaid',
            ]);

            $filter->like('country', 'Country');
            $filter->like('tribe', 'Tribe');
            $filter->between('created_at', 'Registered Date')->date();
        });


        // DIP ID Column
        $grid->column('business_name', __('DIP ID'))
            ->display(function ($business_name) {
                if (empty($business_name)) {
                    return '<span class="label label-default">Not Generated</span>';
                }
                return '<span class="label label-primary" style="font-size: 11px; padding: 4px 8px;">' . $business_name . '</span>';
            })
            ->sortable()
            ->width(90);

        // DTEHM Member ID Column
        $grid->column('dtehm_member_id', __('DTEHM ID'))
            ->display(function ($dtehmId) {
                if (empty($dtehmId)) {
                    return '<span class="text-muted">-</span>';
                }
                return '<span class="label label-success" style="font-size: 10px; padding: 3px 6px;">' . $dtehmId . '</span>';
            })
            ->sortable()
            ->width(100);

        // DTEHM Membership Status
        $grid->column('is_dtehm_member', __('DTEHM Member'))
            ->display(function ($status) {
                if ($status === 'Yes') {
                    return '<span class="label label-success">Yes</span>';
                }
                return '<span class="label label-default">No</span>';
            })
            ->filter([
                'Yes' => 'Yes',
                'No' => 'No',
            ])
            ->width(90);

        // DTEHM Membership Payment Status
        $grid->column('dtehm_membership_is_paid', __('DTEHM Paid'))
            ->display(function ($isPaid) {
                if ($isPaid === 'Yes') {
                    return '<span class="label label-success"><i class="fa fa-check"></i> Paid</span>';
                }
                return '<span class="label label-warning"><i class="fa fa-clock-o"></i> Unpaid</span>';
            })
            ->filter([
                'Yes' => 'Paid',
                'No' => 'Unpaid',
            ])
            ->width(90);

        // Sponsor Column
        $grid->column('sponsor_info', __('Sponsor'))
            ->display(function () {
                if (empty($this->sponsor_id)) {
                    return '<span class="text-muted">-</span>';
                }
                $sponsor = \App\Models\User::where('business_name', $this->sponsor_id)->first();
                if ($sponsor) {
                    return '<span class="label label-success" style="font-size: 10px;">' . $this->sponsor_id . '</span><br>' .
                        '<small class="text-muted">' . $sponsor->name . '</small>';
                }
                return '<span class="label label-warning" style="font-size: 10px;">' . $this->sponsor_id . '</span><br>' .
                    '<small class="text-danger">Not Found</small>';
            })
            ->width(120);

        // Avatar Column


        // Add SMS action column with buttons
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
        $show->field('username', __('Username'));
        $show->field('password', __('Password'));
        $show->field('first_name', __('First name'));
        $show->field('last_name', __('Last name'));
        $show->field('reg_date', __('Reg date'));
        $show->field('last_seen', __('Last seen'));
        $show->field('email', __('Email'));
        $show->field('approved', __('Approved'));
        $show->field('profile_photo', __('Profile photo'));
        $show->field('user_type', __('User type'));
        $show->field('sex', __('Sex'));
        $show->field('reg_number', __('Reg number'));
        $show->field('country', __('Country'));
        $show->field('occupation', __('Occupation'));
        $show->field('profile_photo_large', __('Profile photo large'));
        $show->field('phone_number', __('Phone number'));
        $show->field('location_lat', __('Location lat'));
        $show->field('location_long', __('Location long'));
        $show->field('facebook', __('Facebook'));
        $show->field('twitter', __('Twitter'));
        $show->field('whatsapp', __('Whatsapp'));
        $show->field('linkedin', __('Linkedin'));
        $show->field('website', __('Website'));
        $show->field('other_link', __('Other link'));
        $show->field('cv', __('Cv'));
        $show->field('language', __('Language'));
        $show->field('about', __('About'));
        $show->field('address', __('Address'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('remember_token', __('Remember token'));
        $show->field('avatar', __('Avatar'));
        $show->field('name', __('Name'));
        $show->field('campus_id', __('Campus id'));
        $show->field('complete_profile', __('Complete profile'));
        $show->field('title', __('Title'));
        $show->field('dob', __('Dob'));
        $show->field('intro', __('Intro'));
        $show->field('business_name', __('Business name'));
        $show->field('business_license_number', __('Business license number'));
        $show->field('business_license_issue_authority', __('Business license issue authority'));
        $show->field('business_license_issue_date', __('Business license issue date'));
        $show->field('business_license_validity', __('Business license validity'));
        $show->field('business_address', __('Business address'));
        $show->field('business_phone_number', __('Business phone number'));
        $show->field('business_whatsapp', __('Business whatsapp'));
        $show->field('business_email', __('Business email'));
        $show->field('business_logo', __('Business logo'));
        $show->field('business_cover_photo', __('Business cover photo'));
        $show->field('business_cover_details', __('Business cover details'));

        return $show;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        // First, let parent handle the update
        $response = parent::update($id);
        
        // Then trigger membership creation
        $this->handleMembershipCreation($id);
        
        return $response;
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        // First, let parent handle the creation
        $response = parent::store();
        
        // Get the newly created user ID from the response
        // Laravel-Admin returns redirect, so we need to get the last inserted user
        $user = User::latest('id')->first();
        if ($user) {
            $this->handleMembershipCreation($user->id);
        }
        
        return $response;
    }
    
    /**
     * Handle membership creation for a user
     *
     * @param int $userId
     * @return void
     */
    protected function handleMembershipCreation($userId)
    {
        $admin = Admin::user();
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }
        
        // If no admin user (e.g., CLI context), use a default admin or system user
        if (!$admin) {
            // Try to get first admin user or use null
            $admin = User::where('user_type', 'Admin')->first();
        }
        
        \Log::info('============ HANDLE MEMBERSHIP CREATION ============', [
            'user_id' => $user->id,
            'is_dtehm_member' => $user->is_dtehm_member,
            'is_dip_member' => $user->is_dip_member,
            'admin_id' => $admin ? $admin->id : null,
        ]);
        
        try {
            // Check if user is marked as DTEHM member
            if ($user->is_dtehm_member == 'Yes') {
                // Check if DTEHM membership already exists
                $existingDtehm = \App\Models\DtehmMembership::where('user_id', $user->id)
                    ->where('status', 'CONFIRMED')
                    ->first();
                
                if (!$existingDtehm) {
                    \Log::info('Creating DTEHM membership for user', ['user_id' => $user->id]);
                    
                    $adminId = $admin ? $admin->id : null;
                    $adminUsername = $admin ? $admin->username : 'System';
                    
                    // Create DTEHM Membership (76,000 UGX)
                    $dtehm = \App\Models\DtehmMembership::create([
                        'user_id' => $user->id,
                        'amount' => 76000,
                        'status' => 'CONFIRMED',
                        'payment_method' => 'CASH',
                        'registered_by_id' => $adminId,
                        'created_by' => $adminId,
                        'confirmed_by' => $adminId,
                        'confirmed_at' => now(),
                        'payment_date' => now(),
                        'description' => 'Auto-created by admin ' . $adminUsername . ' via web portal during user registration',
                    ]);
                    
                    // Update user model with DTEHM membership info
                    $user->dtehm_membership_paid_at = now();
                    $user->dtehm_membership_amount = 76000;
                    $user->dtehm_membership_payment_id = $dtehm->id;
                    $user->dtehm_membership_is_paid = 'Yes';
                    $user->dtehm_membership_paid_date = now();
                    $user->dtehm_member_membership_date = now();
                    $user->saveQuietly(); // Use saveQuietly to avoid triggering observer again
                    
                    admin_toastr('DTEHM membership (UGX 76,000) created and marked as PAID', 'success');
                    
                    \Log::info('DTEHM membership created successfully', ['dtehm_id' => $dtehm->id]);
                } else {
                    \Log::info('DTEHM membership already exists', ['user_id' => $user->id]);
                }
            }
            
            // Check if user is marked as DIP member
            if ($user->is_dip_member == 'Yes') {
                // Check if DIP membership already exists
                $existingDip = \App\Models\MembershipPayment::where('user_id', $user->id)
                    ->where('status', 'CONFIRMED')
                    ->first();
                
                if (!$existingDip) {
                    \Log::info('Creating DIP membership for user', ['user_id' => $user->id]);
                    
                    $adminId = $admin ? $admin->id : null;
                    $adminUsername = $admin ? $admin->username : 'System';
                    
                    // Create Regular DIP Membership (20,000 UGX)
                    $membership = \App\Models\MembershipPayment::create([
                        'user_id' => $user->id,
                        'amount' => 20000,
                        'membership_type' => 'LIFE',
                        'status' => 'CONFIRMED',
                        'payment_method' => 'CASH',
                        'created_by' => $adminId,
                        'updated_by' => $adminId,
                        'registered_by_id' => $adminId,
                        'description' => 'Auto-created by admin ' . $adminUsername . ' via web portal during user registration',
                    ]);
                    
                    admin_toastr('DIP membership (UGX 20,000) created and marked as PAID', 'success');
                    
                    \Log::info('DIP membership created successfully', ['membership_id' => $membership->id]);
                } else {
                    \Log::info('DIP membership already exists', ['user_id' => $user->id]);
                }
            }
            
        } catch (\Exception $e) {
            admin_toastr('Membership creation failed: ' . $e->getMessage(), 'error');
            \Log::error('Auto-membership creation failed', [
                'user_id' => $user->id,
                'admin_id' => $admin ? $admin->id : null,
                'is_dtehm_member' => $user->is_dtehm_member,
                'is_dip_member' => $user->is_dip_member,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
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

            $form->divider('Membership Type');
            
            $form->row(function ($row) {
                $row->width(12)->text('sponsor_id', __('Sponsor ID'))
                    ->rules('required')
                    ->placeholder('e.g., DIP0001 or DTEHM20250001')
                    ->help('Required field. Enter DIP ID or DTEHM Member ID of sponsor');
            });

            $form->row(function ($row) {
                $row->width(6)->radio('is_dtehm_member', __('DTEHM Member?'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])
                    ->default('No')
                    ->rules('required')
                    ->help('Is this person a DTEHM member? (76,000 UGX)');

                $row->width(6)->radio('is_dip_member', __('DIP Member?'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])
                    ->default('No')
                    ->rules('required')
                    ->help('Is this person a DIP member? (20,000 UGX)');
            });
            
            $form->html('<div class="alert alert-info">
                <strong>Note:</strong> 
                <ul>
                    <li>Username will be automatically set to the phone number</li>
                    <li>User will be registered under your admin account</li>
                    <li>Membership will be automatically created based on the membership type selected above</li>
                    <li>Default password will be set to the phone number (user can change later)</li>
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



        $form->row(function ($row) {
            $row->width(2)->radio('is_dtehm_member', __('DTEHM Member?'))
                ->options([
                    'Yes' => 'Yes',
                    'No' => 'No',
                ])
                ->default('No')
                ->help('Is this person a DTEHM member?');

            $row->width(2)->radio('is_dip_member', __('DIP Member?'))
                ->options([
                    'Yes' => 'Yes',
                    'No' => 'No',
                ])
                ->default('No')
                ->help('Is this person a DIP member?');
        });

        $form->row(function ($row) {
            $row->width(4)->datetime('dtehm_member_membership_date', __('DTEHM Membership Date'))
                ->help('Date when user became DTEHM member');

            $row->width(4)->radio('dtehm_membership_is_paid', __('DTEHM Membership Paid?'))
                ->options([
                    'Yes' => 'Yes',
                    'No' => 'No',
                ])
                ->default('No')
                ->help('Has membership fee been paid?');

            $row->width(4)->datetime('dtehm_membership_paid_date', __('Payment Date'))
                ->help('Date when membership was paid');
        });

        $form->row(function ($row) {
            $row->width(6)->decimal('dtehm_membership_paid_amount', __('Amount Paid (UGX)'))
                ->help('Amount paid for DTEHM membership');
        });

        $form->row(function ($row) {
            $row->width(4)->radio('user_type', __('User Type'))
                ->options([
                    'Customer' => 'Customer',
                    'Admin' => 'Admin',
                ])
                ->default('Customer')
                ->help('Customer = Insurance User, Admin = System Administrator');

            $row->width(4)->date('dob', __('Date of Birth'))
                ->format('YYYY-MM-DD')
                ->help('Optional field');
        });

        $form->row(function ($row) {
            $row->width(3)->text('sponsor_id', __('Sponsor DIP/DTEHM ID'))
                ->placeholder('e.g., DIP0001 or DTEHM20250001')
                ->help('Can be DIP ID or DTEHM Member ID of sponsor');

            $row->width(3)->image('avatar', __('Profile Photo'))
                ->help('Upload profile photo (optional)')
                ->uniqueName()
                ->move('images/users');

            $row->width(3)->text('business_license_number', __('Group'));

            $roleModel = config('admin.database.roles_model');
            $row->width(3)->multipleSelect('roles', trans('admin.roles'))
                ->options($roleModel::all()->pluck('name', 'id'));
        });


        // SECTION 2: Contact Information
        $form->divider('Contact Information');



        // SECTION 3: Location Information
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
        });

        // SECTION 4: Family Information
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
            $row->width(6)->radio('status', __('Account Status'))
                ->options([
                    'Active' => 'Active',
                    'Pending' => 'Pending',
                    'Inactive' => 'Inactive',
                    'Banned' => 'Banned',
                ])
                ->default('Active');
        });

        // Password fields - show for all admin users creating/editing users
        /*  $form->row(function ($row) {
            $row->width(6)->password('password', __('Password'))
                ->rules('nullable|confirmed|min:6')
                ->help('Leave blank to keep current password (when editing). Minimum 6 characters.')
                ->creationRules('required|min:6');

            $row->width(6)->password('password_confirmation', __('Confirm Password'))
                ->rules('nullable|min:6') 
                ->help('Re-enter password for confirmation');
        }); */

        // Auto-generate name field from first_name and last_name
        $form->saving(function (Form $form) {
            \Log::info('============ SAVING HOOK TRIGGERED ============', [
                'isCreating' => $form->isCreating(),
                'is_dtehm_member' => $form->is_dtehm_member,
                'user_id' => $form->model()->id ?? 'NEW',
            ]);
            
            // Auto-generate full name from first_name and last_name
            if ($form->first_name && $form->last_name) {
                $form->name = trim($form->first_name . ' ' . $form->last_name);
            }

            // FOR NEW USERS: Auto-fill required fields
            if ($form->isCreating()) {
                // 1. Set username to phone_number
                if ($form->phone_number) {
                    $form->username = $form->phone_number;
                }
                
                // 2. Set default password to phone_number (user can change later)
                if ($form->phone_number && !$form->password) {
                    $form->password = bcrypt($form->phone_number);
                }
                
                // 3. Set registered_by_id to current admin
                $form->registered_by_id = \Admin::user()->id;
                
                // 4. Set default values for required fields
                if (!$form->user_type) {
                    $form->user_type = 'Customer';
                }
                
                if (!$form->status) {
                    $form->status = 'Active';
                }
                
                // 5. Set default country
                if (!$form->country) {
                    $form->country = 'Uganda';
                }
                
                // 6. Auto-mark membership paid fields if member type is selected
                if ($form->is_dtehm_member == 'Yes') {
                    $form->dtehm_membership_is_paid = 'Yes';
                    $form->dtehm_membership_paid_date = now();
                    $form->dtehm_membership_paid_amount = 76000;
                    $form->dtehm_member_membership_date = now();
                }
            } else {
                // FOR UPDATES: Auto-mark membership paid fields if changed to Yes
                if ($form->is_dtehm_member == 'Yes' && $form->model()->is_dtehm_member != 'Yes') {
                    $form->dtehm_membership_is_paid = 'Yes';
                    $form->dtehm_membership_paid_date = now();
                    $form->dtehm_membership_paid_amount = 76000;
                    $form->dtehm_member_membership_date = now();
                }
            }

            // Hash password if provided (for updates)
            if ($form->password && !$form->isCreating()) {
                // For updates, check if password has changed
                $originalPassword = $form->model()->getOriginal('password');
                if ($originalPassword != $form->password) {
                    $form->password = bcrypt($form->password);
                }
            } else {
                // Remove password from update if not provided
                if (!$form->isCreating()) {
                    unset($form->password);
                }
            }
        });

        // Success message with validation handling
        $form->saved(function (Form $form) {
            \Log::info('============ SAVED HOOK TRIGGERED ============', [
                'user_id' => $form->model()->id,
            ]);
            
            $admin = \Admin::user();
            $user = $form->model();
            
            // Reload user to get latest data
            $user = \App\Models\User::find($user->id);
            
            try {
                $membershipCreated = false;
                $messages = [];
                
                \Log::info('Checking membership creation', [
                    'user_id' => $user->id,
                    'is_dtehm_member' => $user->is_dtehm_member,
                    'is_dip_member' => $user->is_dip_member,
                ]);
                
                // Check if user is marked as DTEHM member
                if ($user->is_dtehm_member == 'Yes') {
                    // Check if DTEHM membership already exists
                    $existingDtehm = \App\Models\DtehmMembership::where('user_id', $user->id)
                        ->where('status', 'CONFIRMED')
                        ->first();
                    
                    if (!$existingDtehm) {
                        \Log::info('Creating DTEHM membership for user', ['user_id' => $user->id]);
                        
                        // Create DTEHM Membership (76,000 UGX)
                        $dtehm = \App\Models\DtehmMembership::create([
                            'user_id' => $user->id,
                            'amount' => 76000,
                            'status' => 'CONFIRMED',
                            'payment_method' => 'CASH',
                            'registered_by_id' => $admin->id,
                            'created_by' => $admin->id,
                            'confirmed_by' => $admin->id,
                            'confirmed_at' => now(),
                            'payment_date' => now(),
                            'description' => 'Auto-created by admin ' . $admin->username . ' via web portal during user registration',
                        ]);
                        
                        // Update user model with DTEHM membership info
                        $user->dtehm_membership_paid_at = now();
                        $user->dtehm_membership_amount = 76000;
                        $user->dtehm_membership_payment_id = $dtehm->id;
                        $user->dtehm_membership_is_paid = 'Yes';
                        $user->dtehm_membership_paid_date = now();
                        $user->dtehm_member_membership_date = now();
                        $user->save();
                        
                        $membershipCreated = true;
                        $messages[] = 'DTEHM membership (UGX 76,000) created and marked as PAID';
                        
                        \Log::info('DTEHM membership created successfully', ['dtehm_id' => $dtehm->id]);
                    } else {
                        \Log::info('DTEHM membership already exists', ['user_id' => $user->id]);
                    }
                }
                
                // Check if user is marked as DIP member
                if ($user->is_dip_member == 'Yes') {
                    // Check if DIP membership already exists
                    $existingDip = \App\Models\MembershipPayment::where('user_id', $user->id)
                        ->where('status', 'CONFIRMED')
                        ->first();
                    
                    if (!$existingDip) {
                        \Log::info('Creating DIP membership for user', ['user_id' => $user->id]);
                        
                        // Create Regular DIP Membership (20,000 UGX)
                        $membership = \App\Models\MembershipPayment::create([
                            'user_id' => $user->id,
                            'amount' => 20000,
                            'membership_type' => 'LIFE',
                            'status' => 'CONFIRMED',
                            'payment_method' => 'CASH',
                            'created_by_id' => $admin->id,
                            'updated_by_id' => $admin->id,
                            'description' => 'Auto-created by admin ' . $admin->username . ' via web portal during user registration',
                        ]);
                        
                        $membershipCreated = true;
                        $messages[] = 'DIP membership (UGX 20,000) created and marked as PAID';
                        
                        \Log::info('DIP membership created successfully', ['membership_id' => $membership->id]);
                    } else {
                        \Log::info('DIP membership already exists', ['user_id' => $user->id]);
                    }
                }
                
                // Display success message
                if ($membershipCreated) {
                    $action = $form->isCreating() ? 'created' : 'updated';
                    $message = 'User ' . $action . ' successfully';
                    if (count($messages) > 0) {
                        $message .= ' with ' . implode(' and ', $messages);
                    }
                    admin_toastr($message, 'success');
                } else {
                    $action = $form->isCreating() ? 'created' : 'updated';
                    admin_toastr('User ' . $action . ' successfully', 'success');
                }
                
            } catch (\Exception $e) {
                $action = $form->isCreating() ? 'created' : 'updated';
                admin_toastr('User ' . $action . ' but membership creation failed: ' . $e->getMessage(), 'error');
                \Log::error('Auto-membership creation failed', [
                    'user_id' => $user->id,
                    'admin_id' => $admin->id,
                    'is_dtehm_member' => $user->is_dtehm_member,
                    'is_dip_member' => $user->is_dip_member,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
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
