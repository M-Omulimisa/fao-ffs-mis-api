<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\Location;
use App\Models\FfsGroup;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MemberController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Members Management';

    /**
     * Get dynamic title based on URL
     */
    protected function title()
    {
        return 'Members Management';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        
        // Filter only members (not admins)
        $grid->model()->where('user_type', 'Customer')->orderBy('created_at', 'desc');

        // Disable batch actions and deletion
        $grid->disableBatchActions();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });
        
        // Filters
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            
            $filter->equal('group_id', 'Group')->select(FfsGroup::orderBy('name')->pluck('name', 'id'));
            $filter->equal('district_id', 'District')->select(Location::where('type', 'District')->pluck('name', 'id'));
            $filter->like('name', 'Member Name');
            $filter->like('phone_number', 'Phone');
            $filter->equal('sex', 'Gender')->select(['Male' => 'Male', 'Female' => 'Female']);
            $filter->equal('status', 'Status')->select([
                'Active' => 'Active',
                'Inactive' => 'Inactive',
                'Pending' => 'Pending',
            ]);
        });
        
        // Columns
        $grid->column('id', 'ID')->sortable()->hide();
        $grid->column('member_code', 'Code')->label('primary')->copyable()->sortable();
        
        $grid->column('avatar', 'Photo')
            ->lightbox(['width' => 50, 'height' => 50]);
        
        $grid->column('name', 'Member Name')->display(function($name) {
            $firstName = $this->first_name ?? '';
            $lastName = $this->last_name ?? '';
            $fullName = trim($firstName . ' ' . $lastName) ?: $name;
            return "<strong>$fullName</strong>";
        })->sortable();
        
        $grid->column('sex', 'Gender')->label([
            'Male' => 'info',
            'Female' => 'danger',
        ])->sortable();
        
        $grid->column('phone_number', 'Phone')->sortable();
        
        $grid->column('district', 'District')->display(function() {
            if ($this->district_id) {
                $district = Location::find($this->district_id);
                return $district ? $district->name : 'N/A';
            }
            return 'N/A';
        });
        
        $grid->column('village', 'Village');
        
        $grid->column('group', 'Group')->display(function() {
            return $this->group ? $this->group->name : '<span style="color: #999;">Not Assigned</span>';
        });
        
        $grid->column('dob', 'Age')->display(function($dob) {
            if (!$dob) return 'N/A';
            $age = \Carbon\Carbon::parse($dob)->age;
            return $age . ' yrs';
        })->sortable();
        
        $grid->column('status', 'Status')->label([
            'Active' => 'success',
            'Inactive' => 'default',
            'Pending' => 'warning',
        ])->sortable();
        
        $grid->column('created_at', 'Registered')->display(function($date) {
            return date('d M Y', strtotime($date));
        })->sortable();
        
        // Add SMS action column with two buttons
        $grid->column('sms_actions', 'SMS')->display(function () {
            $userId = $this->id;
            $phone = $this->phone_number;
            
            if (empty($phone)) {
                return '<span class="text-muted"><i class="fa fa-phone-slash"></i> No Phone</span>';
            }
            
            return '
                <a href="' . admin_url('ffs-members/' . $userId . '/send-credentials') . '" 
                   class="btn btn-xs btn-success" 
                   title="Send login credentials via SMS">
                    <i class="fa fa-key"></i> Credentials
                </a>
                <br style="margin: 2px 0;">
                <a href="' . admin_url('ffs-members/' . $userId . '/send-welcome') . '" 
                   class="btn btn-xs btn-info" 
                   title="Send welcome message via SMS"
                   style="margin-top: 3px;">
                    <i class="fa fa-envelope"></i> Welcome
                </a>
            ';
        })->width(120);
        
        // Quick create
        $grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
            $create->text('first_name', 'First Name')->required();
            $create->text('last_name', 'Last Name')->required();
            $create->text('phone_number', 'Phone Number')->required();
            $create->select('sex', 'Gender')->options(['Male' => 'Male', 'Female' => 'Female'])->required();
            $create->select('group_id', 'Group')->options(FfsGroup::where('status', 'Active')->orderBy('name')->pluck('name', 'id'));
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
        $show = new Show(User::findOrFail($id));
        
        $show->panel()->style('primary')->title('Member Details');

        // Basic Information
        $show->divider('Basic Information');
        $show->field('member_code', 'Member Code')->label('primary');
        $show->field('first_name', 'First Name');
        $show->field('last_name', 'Last Name');
        $show->field('sex', 'Gender');
        $show->field('dob', 'Date of Birth')->as(function($date) {
            return $date ? date('d M Y', strtotime($date)) : 'N/A';
        });
        $show->field('marital_status', 'Marital Status');
        $show->field('education_level', 'Education Level');
        
        // Contact Information
        $show->divider('Contact Information');
        $show->field('phone_number', 'Primary Phone');
        $show->field('phone_number_2', 'Secondary Phone');
        $show->field('email', 'Email');
        $show->field('emergency_contact_name', 'Emergency Contact');
        $show->field('emergency_contact_phone', 'Emergency Phone');
        
        // Group Assignment
        $show->divider('Group Assignment');
        $show->field('group', 'Group')->as(function() {
            return $this->group ? $this->group->name : 'Not Assigned';
        });
        
        // Location
        $show->divider('Location Information');
        $show->field('district', 'District')->as(function() {
            return $this->district ? $this->district->name : 'N/A';
        });
        $show->field('subcounty', 'Subcounty')->as(function() {
            return $this->subcounty ? $this->subcounty->name : 'N/A';
        });
        $show->field('parish', 'Parish')->as(function() {
            return $this->parish ? $this->parish->name : 'N/A';
        });
        $show->field('village', 'Village');
        $show->field('address', 'Home Address');
        
        // Family Information
        $show->divider('Family Information');
        $show->field('father_name', "Father's Name");
        $show->field('mother_name', "Mother's Name");
        $show->field('household_size', 'Household Size');
        
        // Additional Information
        $show->divider('Additional Information');
        $show->field('occupation', 'Occupation');
        $show->field('skills', 'Skills');
        $show->field('disabilities', 'Disabilities');
        $show->field('remarks', 'Remarks');
        
        // Photo
        $show->field('avatar', 'Photo')->image();
        
        // Account Status
        $show->divider('Account Information');
        $show->field('username', 'Username');
        $show->field('status', 'Status');
        $show->field('created_at', 'Registered')->as(function($date) {
            return date('d M Y H:i:s', strtotime($date));
        });

        return $show;
    }

    /**
     * Override update method to add logging
     */
    public function update($id)
    {
        Log::info('=== UPDATE METHOD CALLED ===', [
            'id' => $id,
            'input' => request()->all(),
        ]);
        
        return $this->form()->update($id);
    }
    
    /**
     * Send login credentials SMS to member
     */
    public function sendCredentials($id)
    {
        $user = User::findOrFail($id);
        
        // Validate phone number
        if (empty($user->phone_number)) {
            admin_toastr('Member has no phone number', 'error');
            return redirect()->back();
        }
        
        // SMS message - Login credentials (under 160 chars)
        $message = "FAO FFS-MIS Login\n";
        $message .= "Username: " . $user->username . "\n";
        $message .= "Password: " . $user->phone_number . "\n";
        $message .= "Visit: localhost:8888";
        
        try {
            // Send SMS using Utils service
            $response = \App\Models\Utils::sendSMS($user->phone_number, $message);
            
            // Log the SMS
            Log::info('=== CREDENTIALS SMS SENT ===', [
                'member_id' => $user->id,
                'member_name' => $user->name,
                'phone' => $user->phone_number,
                'message_length' => strlen($message),
                'response' => $response,
            ]);
            
            admin_toastr('Credentials SMS sent to ' . $user->name, 'success');
            
        } catch (\Exception $e) {
            Log::error('=== CREDENTIALS SMS FAILED ===', [
                'member_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            admin_toastr('Failed to send SMS: ' . $e->getMessage(), 'error');
        }
        
        return redirect()->back();
    }
    
    /**
     * Send welcome SMS to member
     */
    public function sendWelcome($id)
    {
        $user = User::findOrFail($id);
        
        // Validate phone number
        if (empty($user->phone_number)) {
            admin_toastr('Member has no phone number', 'error');
            return redirect()->back();
        }
        
        // SMS message - Welcome message (under 160 chars)
        $message = "Welcome to FAO FFS-MIS!\n";
        $message .= "Dear " . $user->first_name . ",\n";
        $message .= "You are now registered as a member.\n";
        $message .= "Thank you for joining us!";
        
        try {
            // Send SMS using Utils service
            $response = \App\Models\Utils::sendSMS($user->phone_number, $message);
            
            // Log the SMS
            Log::info('=== WELCOME SMS SENT ===', [
                'member_id' => $user->id,
                'member_name' => $user->name,
                'phone' => $user->phone_number,
                'message_length' => strlen($message),
                'response' => $response,
            ]);
            
            admin_toastr('Welcome SMS sent to ' . $user->name, 'success');
            
        } catch (\Exception $e) {
            Log::error('=== WELCOME SMS FAILED ===', [
                'member_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            admin_toastr('Failed to send SMS: ' . $e->getMessage(), 'error');
        }
        
        return redirect()->back();
    }
    
    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        Log::info('=== MEMBER FORM METHOD CALLED ===', [
            'request_method' => request()->method(),
            'url' => request()->url(),
            'all_input' => request()->all(),
        ]);
        
        $form = new Form(new User());
        
        // Hidden fields
        $form->hidden('user_type')->default('Customer');
        
        // Basic Information
        $form->divider('Basic Information');
        
        $form->row(function ($row) {
            $row->width(6)->text('first_name', 'First Name')->required();
            $row->width(6)->text('last_name', 'Last Name')->required();
        });
        
        $form->row(function ($row) {
            $row->width(6)->radio('sex', 'Gender')->options(['Male' => 'Male', 'Female' => 'Female'])->default('Male');
            $row->width(6)->date('dob', 'Date of Birth');
        });
        
        $form->row(function ($row) {
            $row->width(6)->radio('marital_status', 'Marital Status')->options([
                'Single' => 'Single',
                'Married' => 'Married',
                'Divorced' => 'Divorced',
                'Widowed' => 'Widowed',
            ]);
            $row->width(6)->radio('education_level', 'Education Level')->options([
                'None' => 'None',
                'Primary' => 'Primary',
                'Secondary' => 'Secondary',
                'Tertiary' => 'Tertiary/University',
            ]);
        });
        
        $form->row(function ($row) {
            $row->width(6)->decimal('household_size', 'Household Size')->help('Number of people in household');
            $row->width(6)->text('occupation', 'Occupation');
        });
        
        // Contact Information
        $form->divider('Contact Information');
        
        $form->row(function ($row) {
            $row->width(6)->text('phone_number', 'Primary Phone')->rules('unique:users,phone_number,{{id}}');
            $row->width(6)->text('phone_number_2', 'Secondary Phone');
        });
        
        $form->row(function ($row) {
            $row->width(6)->email('email', 'Email Address');
            $row->width(6)->text('emergency_contact_name', 'Emergency Contact Name');
        });
        
        $form->row(function ($row) {
            $row->width(6)->mobile('emergency_contact_phone', 'Emergency Contact Phone');
            $row->width(6)->text('village', 'Village');
        });
        
        // Group Assignment & Location
        $form->divider('Group Assignment & Location');
        
        $groups = FfsGroup::where('status', 'Active')->orderBy('name')->get()->pluck('name', 'id');
        $districts = Location::where('type', 'District')->pluck('name', 'id');
        
        $form->row(function ($row) use ($groups, $districts) {
            $row->width(6)->select('group_id', 'Assign to Group')->options($groups)->help('Optional - can be assigned later');
            $row->width(6)->select('district_id', 'District')->options($districts);
        });
        
        $form->row(function ($row) {
            $row->width(6)->select('subcounty_id', 'Subcounty');
            $row->width(6)->select('parish_id', 'Parish');
        });
        
        $form->row(function ($row) {
            $row->width(6)->text('address', 'Home Address');
            $row->width(6)->textarea('skills', 'Skills')->rows(2);
        });
        
        // Family Information
        $form->divider('Family Information');
        
        $form->row(function ($row) {
            $row->width(6)->text('father_name', "Father's Name");
            $row->width(6)->text('mother_name', "Mother's Name");
        });
        
        // Additional Information
        $form->divider('Additional Information');
        
        $form->row(function ($row) {
            $row->width(6)->textarea('disabilities', 'Disabilities')->rows(2);
            $row->width(6)->textarea('remarks', 'Remarks')->rows(2);
        });
        
        $form->row(function ($row) {
            $row->width(6)->image('avatar', 'Profile Photo');
        });
        
        // Account Settings
        $form->divider('Account Settings');
        
        $form->row(function ($row) {
            $row->width(6)->text('username', 'Username')
                ->creationRules(['unique:users,username'])
                ->updateRules(['unique:users,username,{{id}}'])
                ->help('Will auto-fill with phone number if left blank');
            
            $row->width(6)->radio('status', 'Status')->options([
                'Active' => 'Active',
                'Inactive' => 'Inactive',
                'Pending' => 'Pending',
            ])->default('Active');
        });
        
        if ($form->isCreating()) {
            $form->password('password', 'Password')
                ->rules('nullable|min:6')
                ->help('Leave blank to auto-set as phone number');
        } else {
            $form->password('password', 'New Password')
                ->rules('nullable|min:6')
                ->help('Leave blank to keep current password');
        }
        
        // Saving logic
        $form->saving(function (Form $form) {
            Log::info('=== MEMBER FORM SAVING ===', [
                'isCreating' => $form->isCreating(),
                'user_id' => $form->model()->id ?? 'NEW',
                'first_name' => $form->first_name,
                'last_name' => $form->last_name,
                'phone' => $form->phone_number,
                'group_id' => $form->group_id,
            ]);
            
            // Auto-generate full name
            if ($form->first_name && $form->last_name) {
                $form->name = trim($form->first_name . ' ' . $form->last_name);
            }

            // For new members
            if ($form->isCreating()) {
                // Set username to phone if empty
                if (!$form->username && $form->phone_number) {
                    $form->username = $form->phone_number;
                } elseif (!$form->username) {
                    $form->username = 'user_' . time();
                }
                
                // Set password to phone if empty
                if (!$form->password && $form->phone_number) {
                    $form->password = bcrypt($form->phone_number);
                } elseif (!$form->password) {
                    $form->password = bcrypt('123456');
                } elseif ($form->password) {
                    $form->password = bcrypt($form->password);
                }
                
                // Set created_by_id
                $form->created_by_id = \Encore\Admin\Facades\Admin::user()->id;
                $form->registered_by_id = \Encore\Admin\Facades\Admin::user()->id;
                
                Log::info('Creating new member with username: ' . $form->username);
            } else {
                Log::info('Updating member', [
                    'id' => $form->model()->id,
                    'changes' => $form->model()->getDirty()
                ]);
                
                // For updates, only hash password if provided
                if ($form->password) {
                    $form->password = bcrypt($form->password);
                } else {
                    unset($form->password);
                }
            }
        });
        
        $form->saved(function (Form $form) {
            Log::info('=== MEMBER SAVED ===', [
                'user_id' => $form->model()->id,
                'name' => $form->model()->name,
                'group_id' => $form->model()->group_id,
            ]);
        });
        
        // Ignore password confirmation
        $form->ignore(['password_confirmation']);
        
        // Form configuration
        $form->disableViewCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
