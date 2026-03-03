<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\Location;
use App\Models\FfsGroup;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MemberController extends AdminController
{
    use IpScopeable;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Group Members';

    /**
     * Get dynamic title based on URL
     */
    protected function title()
    {
        return 'Group Members';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->model()->orderBy('id', 'desc');
        $this->applyIpScope($grid);
        
        
        $grid->quickSearch('name', 'phone_number', 'phone_number_2')->placeholder('Search member name, phone...');

        // Disable batch deletion but allow batch export
        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });
        
        // Filters
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $this->addIpFilter($filter);
            
            // Group filters
            $filter->equal('group_id', 'FFS Group')->select(function() {
                return FfsGroup::where('status', 'Active')
                    ->orderBy('name')
                    ->pluck('name', 'id');
            });
            
            $filter->equal('is_group_admin', 'Position')->select([
                'Yes' => 'Chairperson',
                'No' => 'Regular Member',
            ]);
            
            // Location filters
            $filter->equal('district_id', 'District')->select(function() {
                return Location::where('parent', 0)
                    ->orderBy('name')
                    ->pluck('name', 'id');
            });
            
            $filter->equal('subcounty_id', 'Subcounty')->select(function() {
                return Location::where('parent', '>', 0)
                    ->orderBy('name')
                    ->pluck('name', 'id');
            });
            
            // Demographics
            $filter->equal('sex', 'Gender')->select([
                'Male' => 'Male', 
                'Female' => 'Female'
            ]);
            
            $filter->between('dob', 'Age Range')->date();
            
            // Account status
            $filter->equal('status', 'Account Status')->select([
                '1' => 'Active',
                '0' => 'Inactive',
            ]);
        });
        
        // Columns
        $grid->column('id', 'ID')->sortable();
        
        $grid->column('avatar', 'Photo')->image('', 50, 50);
        
        $grid->column('name', 'Full Name')->display(function() {
            $name = $this->name ?: ($this->first_name . ' ' . $this->last_name);
            $html = "<strong>{$name}</strong><br>";
            
            // Show position badge if chairperson
            if ($this->is_group_admin == 'Yes') {
                $html .= '<span class="label label-primary"><i class="fa fa-star"></i> Chairperson</span>';
            } elseif ($this->is_group_secretary == 'Yes') {
                $html .= '<span class="label label-info"><i class="fa fa-pencil"></i> Secretary</span>';
            } elseif ($this->is_group_treasurer == 'Yes') {
                $html .= '<span class="label label-warning"><i class="fa fa-money"></i> Treasurer</span>';
            }
            
            return $html;
        })->sortable();
        
        $grid->column('phone_number', 'Contact')->display(function() {
            $html = '<i class="fa fa-phone text-success"></i> ' . $this->phone_number;
            if ($this->phone_number_2) {
                $html .= '<br><i class="fa fa-phone text-muted"></i> ' . $this->phone_number_2;
            }
            return $html;
        });
        
        $grid->column('sex', 'Gender')->using([
            'Male' => 'Male',
            'Female' => 'Female',
        ])->dot([
            'Male' => 'primary',
            'Female' => 'danger',
        ], 'warning')->sortable();
        
        $grid->column('dob', 'Age')->display(function($dob) {
            if (!$dob) return '-';
            $age = \Carbon\Carbon::parse($dob)->age;
            return $age . ' yrs';
        })->sortable();
        
        $grid->column('group.name', 'FFS Group')->display(function() {
            if (!$this->group) {
                return '<span class="text-muted">Not Assigned</span>';
            }
            
            $type = $this->group->type;
            $typeLabel = [
                'FFS' => 'success',
                'FBS' => 'primary', 
                'VSLA' => 'warning',
                'Association' => 'info',
            ];
            
            return '<span class="label label-' . ($typeLabel[$type] ?? 'default') . '">' . $type . '</span><br>' . 
                   '<strong>' . $this->group->name . '</strong>';
        });
        
        // Implementing Partner
        $grid->column('ip_id', 'IP')->display(function () {
            if ($this->ip_id) {
                $ip = ImplementingPartner::find($this->ip_id);
                if ($ip) {
                    $name = $ip->short_name ?: $ip->name;
                    return "<span class='label label-primary'>{$name}</span>";
                }
            }
            // Fallback: try to get from group
            if ($this->group && $this->group->ip_id) {
                $ip = ImplementingPartner::find($this->group->ip_id);
                if ($ip) {
                    $name = $ip->short_name ?: $ip->name;
                    return "<span class='label label-default'>{$name}</span>";
                }
            }
            return '<span style="color:#999;">-</span>';
        })->sortable();
        
        $grid->column('location', 'Location')->display(function() {
            $parts = [];
            if ($this->village) $parts[] = $this->village;
            if ($this->parish_id) {
                $parish = Location::find($this->parish_id);
                if ($parish) $parts[] = $parish->name;
            }
            if ($this->subcounty_id) {
                $subcounty = Location::find($this->subcounty_id);
                if ($subcounty) $parts[] = $subcounty->name;
            }
            if ($this->district_id) {
                $district = Location::find($this->district_id);
                if ($district) $parts[] = '<strong>' . $district->name . '</strong>';
            }
            return implode(', ', $parts) ?: 'N/A';
        });
        
        $grid->column('status', 'Status')->display(function() {
            return $this->status == 1 ? 
                '<span class="label label-success">Active</span>' : 
                '<span class="label label-default">Inactive</span>';
        })->sortable();
        
        $grid->column('onboarding_step', 'Onboarding')->display(function($step) {
            $labels = [
                'not_started'         => ['Not Started', 'default'],
                'step_1_welcome'      => ['Welcome', 'default'],
                'step_2_terms'        => ['Terms', 'info'],
                'step_3_registration' => ['Registered', 'info'],
                'step_4_group'        => ['Group', 'primary'],
                'step_5_members'      => ['Members', 'primary'],
                'step_6_cycle'        => ['Cycle', 'warning'],
                'step_7_complete'     => ['Complete', 'success'],
            ];
            $info = $labels[$step] ?? ['Unknown', 'default'];
            return '<span class="label label-' . $info[1] . '">' . $info[0] . '</span>';
        })->sortable();

        $grid->column('created_at', 'Registered')->display(function($date) {
            return \Carbon\Carbon::parse($date)->format('d M Y');
        })->sortable();

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
        
        $show->panel()->style('success')->title('Member Profile');

        // Profile Photo
        $show->field('avatar', 'Photo')->image('', 150, 150);

        // Basic Information
        $show->divider('Personal Information');
        $show->field('name', 'Full Name');
        $show->field('sex', 'Gender')->using(['Male' => 'Male', 'Female' => 'Female']);
        $show->field('dob', 'Date of Birth')->as(function($date) {
            if (!$date) return 'N/A';
            $age = \Carbon\Carbon::parse($date)->age;
            return date('d M Y', strtotime($date)) . " ({$age} years old)";
        });
        $show->field('marital_status', 'Marital Status');
        $show->field('education_level', 'Education Level');
        $show->field('occupation', 'Occupation');
        
        // Contact Information
        $show->divider('Contact Details');
        $show->field('phone_number', 'Primary Phone')->as(function($phone) {
            return $phone ? '<a href="tel:' . $phone . '">' . $phone . '</a>' : 'N/A';
        })->unescape();
        $show->field('phone_number_2', 'Secondary Phone')->as(function($phone) {
            return $phone ? '<a href="tel:' . $phone . '">' . $phone . '</a>' : 'N/A';
        })->unescape();
        $show->field('email', 'Email Address')->as(function($email) {
            return $email ? '<a href="mailto:' . $email . '">' . $email . '</a>' : 'N/A';
        })->unescape();
        
        // FFS Group Information
        $show->divider('FFS Group Membership');
        $show->field('group.name', 'Group Name');
        $show->field('group.type', 'Group Type');
        $show->field('is_group_admin', 'Position')->using([
            'Yes' => '⭐ Chairperson',
            'No' => 'Member',
        ]);
        $show->field('is_group_secretary', 'Secretary Role')->using([
            'Yes' => '✓ Yes',
            'No' => '✗ No',
        ]);
        $show->field('is_group_treasurer', 'Treasurer Role')->using([
            'Yes' => '✓ Yes',
            'No' => '✗ No',
        ]);
        
        // Location
        $show->divider('Location');
        $show->field('district_id', 'District')->as(function() {
            $location = Location::find($this->district_id);
            return $location ? $location->name : 'N/A';
        });
        $show->field('subcounty_id', 'Subcounty')->as(function() {
            $location = Location::find($this->subcounty_id);
            return $location ? $location->name : 'N/A';
        });
        $show->field('parish_id', 'Parish')->as(function() {
            $location = Location::find($this->parish_id);
            return $location ? $location->name : 'N/A';
        });
        $show->field('village', 'Village');
        $show->field('address', 'Home Address');
        
        // Household Information
        $show->divider('Household Information');
        $show->field('household_size', 'Household Size')->as(function($size) {
            return $size ? $size . ' people' : 'N/A';
        });
        $show->field('father_name', "Father's Name");
        $show->field('mother_name', "Mother's Name");
        
        // Emergency Contact
        $show->divider('Emergency Contact');
        $show->field('emergency_contact_name', 'Contact Name');
        $show->field('emergency_contact_phone', 'Contact Phone')->as(function($phone) {
            return $phone ? '<a href="tel:' . $phone . '">' . $phone . '</a>' : 'N/A';
        })->unescape();
        
        // Additional Information
        $show->divider('Additional Information');
        $show->field('skills', 'Skills & Expertise');
        $show->field('disabilities', 'Special Needs/Disabilities');
        $show->field('remarks', 'Additional Notes');
        
        // National ID
        $show->divider('Identity & Household');
        $show->field('national_id_number', 'National ID (NIN)');
        $show->field('household_size', 'Household Size')->as(function($size) {
            return $size ? $size . ' people' : 'N/A';
        });

        // Account Information
        $show->divider('Account Status & Onboarding');
        $show->field('username', 'Login Username');
        $show->field('status', 'Account Status')->using([
            '1' => '✓ Active',
            '0' => '✗ Inactive',
        ]);
        $show->field('onboarding_step', 'Onboarding Step')->using([
            'not_started'         => '0 - Not Started',
            'step_1_welcome'      => '1 - Welcome Seen',
            'step_2_terms'        => '2 - Terms Accepted',
            'step_3_registration' => '3 - Registered',
            'step_4_group'        => '4 - Group Created',
            'step_5_members'      => '5 - Members Registered',
            'step_6_cycle'        => '6 - Cycle Configured',
            'step_7_complete'     => '7 - Onboarding Complete',
        ]);
        $show->field('created_at', 'Registration Date')->as(function($date) {
            return \Carbon\Carbon::parse($date)->format('d M Y H:i');
        });
        $show->field('registered_by_id', 'Registered By')->as(function($id) {
            if (!$id) return 'N/A';
            $user = User::find($id);
            return $user ? $user->name : 'Unknown';
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

        // IP scope guard
        if (!$this->verifyIpAccess($user)) {
            return $this->denyIpAccess();
        }

        // Validate phone number
        if (empty($user->phone_number)) {
            admin_toastr('Member has no phone number on file', 'error');
            return redirect()->back();
        }
        
        // Prepare SMS message
        $firstName = $user->first_name ?: explode(' ', $user->name)[0];
        $username = $user->username ?: $user->phone_number;
        
        // Extract digits from phone for password
        $password = preg_replace('/[^0-9]/', '', $user->phone_number);
        
        $message = "FAO FFS-MIS - Login Credentials\n\n";
        $message .= "Dear {$firstName},\n";
        $message .= "Username: {$username}\n";
        $message .= "Password: {$password}\n\n";
        $message .= "Download the app from Play Store or contact your administrator.";
        
        try {
            Log::info('Attempting to send credentials SMS', [
                'member_id' => $user->id,
                'member_name' => $user->name,
                'phone' => $user->phone_number,
                'message' => $message,
            ]);
            
            $response = \App\Models\Utils::send_sms($user->phone_number, $message);
            
            Log::info('Credentials SMS sent successfully', [
                'member_id' => $user->id,
                'response' => $response,
            ]);
            
            admin_toastr("Login credentials sent to {$user->name} ({$user->phone_number})", 'success');
            
        } catch (\Exception $e) {
            Log::error('Credentials SMS failed', [
                'member_id' => $user->id,
                'phone' => $user->phone_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

        // IP scope guard
        if (!$this->verifyIpAccess($user)) {
            return $this->denyIpAccess();
        }

        // Validate phone number
        if (empty($user->phone_number)) {
            admin_toastr('Member has no phone number on file', 'error');
            return redirect()->back();
        }
        
        // Prepare welcome message
        $firstName = $user->first_name ?: explode(' ', $user->name)[0];
        $groupName = $user->group ? $user->group->name : 'your group';
        
        $message = "Welcome to FAO FFS-MIS!\n\n";
        $message .= "Dear {$firstName},\n";
        $message .= "You have been successfully registered as a member of {$groupName}.\n\n";
        $message .= "You will receive login credentials shortly. Thank you for joining us!";
        
        try {
            Log::info('Attempting to send welcome SMS', [
                'member_id' => $user->id,
                'member_name' => $user->name,
                'phone' => $user->phone_number,
                'message' => $message,
            ]);
            
            $response = \App\Models\Utils::send_sms($user->phone_number, $message);
            
            Log::info('Welcome SMS sent successfully', [
                'member_id' => $user->id,
                'response' => $response,
            ]);
            
            admin_toastr("Welcome message sent to {$user->name} ({$user->phone_number})", 'success');
            
        } catch (\Exception $e) {
            Log::error('Welcome SMS failed', [
                'member_id' => $user->id,
                'phone' => $user->phone_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        $form = new Form(new User());
        $this->addIpFieldToForm($form);
        
        // Hidden fields
        $form->hidden('user_type')->default('Customer');
        
        // Personal Information
        $form->row(function ($row) {
            $row->width(4)->text('first_name', 'First Name')->required();
            $row->width(4)->text('last_name', 'Last Name')->required();
            $row->width(4)->radio('sex', 'Gender')->options(['Male' => 'Male', 'Female' => 'Female'])->default('Male')->required();
        });
        
        $form->row(function ($row) {
            $row->width(4)->date('dob', 'Date of Birth')->help('Optional but helps for age reporting');
            $row->width(4)->select('marital_status', 'Marital Status')->options([
                'Single' => 'Single', 'Married' => 'Married', 'Divorced' => 'Divorced', 
                'Widowed' => 'Widowed', 'Separated' => 'Separated'
            ])->default('Single');
            $row->width(4)->select('education_level', 'Education Level')->options([
                'None' => 'No Formal Education', 'Primary' => 'Primary', 'O-Level' => 'O-Level', 
                'A-Level' => 'A-Level', 'Certificate' => 'Certificate', 'Diploma' => 'Diploma', 
                'Degree' => 'Degree', 'Masters' => 'Masters', 'PhD' => 'PhD'
            ])->default('Primary');
        });
        
        $form->row(function ($row) {
            $row->width(6)->text('occupation', 'Occupation')->default('Farmer')->placeholder('e.g. Farmer, Trader, Teacher');
            $row->width(6)->image('avatar', 'Photo')->removable();
        });
        
        // Contact Information
        $form->row(function ($row) {
            $row->width(4)->text('phone_number', 'Primary Phone')
                ->placeholder('e.g. 0771234567')
                ->creationRules(['required', 'unique:users,phone_number'])
                ->updateRules(['required', 'unique:users,phone_number,{{id}}'])
                ->help('Used as login username & password');
            $row->width(4)->text('phone_number_2', 'Alternative Phone')->placeholder('Optional second number');
            $row->width(4)->email('email', 'Email')->placeholder('Optional email address');
        });
        
        // Group Assignment (IP-scoped for non-super-admins)
        $form->row(function ($row) {
            $ipId = $this->getAdminIpId();
            $row->width(6)->select('group_id', 'Group')->options(function() use ($ipId) {
                $query = FfsGroup::where('status', 'Active')
                    ->orderBy('type')->orderBy('name');
                if ($ipId !== null) {
                    $query->where('ip_id', $ipId);
                }
                return $query->get()->mapWithKeys(function($group) {
                    return [$group->id => "[{$group->type}] {$group->name}"];
                });
            })->help('Only active groups shown. Group determines the member\'s IP.');
            $row->width(6)->radio('status', 'Account Status')->options(['1' => 'Active', '0' => 'Inactive'])->default('1');
        });
        
        $form->row(function ($row) {
            $row->width(4)->radio('is_group_admin', 'Chairperson?')->options(['Yes' => 'Yes', 'No' => 'No'])->default('No');
            $row->width(4)->radio('is_group_secretary', 'Secretary?')->options(['Yes' => 'Yes', 'No' => 'No'])->default('No');
            $row->width(4)->radio('is_group_treasurer', 'Treasurer?')->options(['Yes' => 'Yes', 'No' => 'No'])->default('No');
        });
        
        // Location
        $form->row(function ($row) {
            $row->width(3)->select('district_id', 'District')->options(function() {
                return Location::where('parent', 0)->orderBy('name')->pluck('name', 'id');
            });
            $row->width(3)->select('subcounty_id', 'Subcounty')->options(function() {
                // Load all subcounties; JS dependency would require custom JS, so show all for now
                return Location::where('parent', '>', 0)
                    ->whereHas('parent_location', function($q) {
                        $q->where('parent', 0);
                    })
                    ->orderBy('name')
                    ->pluck('name', 'id');
            })->help('Optional: Select subcounty');
            $row->width(3)->select('parish_id', 'Parish')->options(function() {
                return Location::whereHas('parent_location', function($q) {
                    $q->where('parent', '>', 0);
                })->orderBy('name')->pluck('name', 'id');
            })->help('Optional: Select parish');
            $row->width(3)->text('village', 'Village');
        });

        $form->text('address', 'Home Address')->placeholder('Physical address');

        // Household & Family
        $form->divider('Household & Family');
        $form->row(function ($row) {
            $row->width(4)->text('national_id_number', 'National ID (NIN)')->placeholder('e.g. CM20000680KBZN');
            $row->width(4)->number('household_size', 'Household Size')->default(1)->min(1)->help('Number of people in household');
            $row->width(4)->select('onboarding_step', 'Onboarding Step')->options([
                'not_started'         => '0 - Not Started',
                'step_1_welcome'      => '1 - Welcome Seen',
                'step_2_terms'        => '2 - Terms Accepted',
                'step_3_registration' => '3 - Registered',
                'step_4_group'        => '4 - Group Created',
                'step_5_members'      => '5 - Members Registered',
                'step_6_cycle'        => '6 - Cycle Configured',
                'step_7_complete'     => '7 - Onboarding Complete',
            ])->default('not_started')->help('Controls where mobile app resumes onboarding');
        });

        // Parents' names (used in household section)
        $form->row(function ($row) {
            $row->width(6)->text('father_name', "Father's Name");
            $row->width(6)->text('mother_name', "Mother's Name");
        });

        // Emergency Contact
        $form->row(function ($row) {
            $row->width(6)->text('emergency_contact_name', 'Emergency Contact Name');
            $row->width(6)->mobile('emergency_contact_phone', 'Emergency Contact Phone');
        });

        // Additional
        $form->row(function ($row) {
            $row->width(6)->textarea('skills', 'Skills')->rows(2);
            $row->width(6)->textarea('disabilities', 'Special Needs')->rows(2);
        });

        $form->textarea('remarks', 'Remarks')->rows(2);

        // Account & Security
        $form->divider('Account & Security');

        // Role assignment - IP admins can only assign IP-level and below roles
        $form->row(function ($row) use ($form) {
            $roleModel = config('admin.database.roles_model');
            $rolesQuery = $roleModel::query();
            if (!$this->isSuperAdmin()) {
                $rolesQuery->where('slug', '!=', 'super_admin');
            }
            $row->width(6)->multipleSelect('roles', 'Roles')
                ->options($rolesQuery->pluck('name', 'id'))
                ->help('Assign system roles to this member (optional)');

            if ($form->isCreating()) {
                $row->width(6)->password('password', 'Password')
                    ->help('Optional. If blank, phone number will be used as default password.');
            } else {
                $row->width(6)->password('password', 'Change Password')
                    ->help('Leave blank to keep current password');
            }
        });

        if ($form->isCreating()) {
            $form->text('username', 'Username (optional)');
        }
        
        // Saving logic
        $form->saving(function (Form $form) {
            // Build full name
            $form->name = trim($form->first_name . ' ' . $form->last_name);

            if ($form->isCreating()) {
                // Auto-generate username from phone if empty
                if (empty($form->username) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                } elseif (empty($form->username)) {
                    $form->username = 'member_' . time();
                }
                
                // Set default password to phone number
                if (empty($form->password)) {
                    $plainPassword = !empty($form->phone_number) ? 
                        preg_replace('/[^0-9]/', '', $form->phone_number) : '123456';
                    $form->password = bcrypt($plainPassword);
                } else {
                    $form->password = bcrypt($form->password);
                }
                
                $form->created_by_id = \Encore\Admin\Facades\Admin::user()->id;
                $form->registered_by_id = \Encore\Admin\Facades\Admin::user()->id;

                // Smart onboarding: if admin creates member with group + full data,
                // auto-advance onboarding so mobile app doesn't force them through steps
                if ($form->onboarding_step === 'not_started' || empty($form->onboarding_step)) {
                    if (!empty($form->group_id)) {
                        // If member has a group assigned by admin, skip to at least step_5
                        // Check if the group already has an active cycle
                        $activeCycle = \App\Models\Project::where('group_id', $form->group_id)
                            ->where('is_vsla_cycle', 'Yes')
                            ->where('is_active_cycle', 'Yes')
                            ->first();
                        if ($activeCycle) {
                            $form->onboarding_step = 'step_7_complete';
                            $form->onboarding_completed_at = now();
                        } else {
                            $form->onboarding_step = 'step_5_members';
                        }
                    } elseif (!empty($form->first_name) && !empty($form->phone_number)) {
                        // Has basic registration data but no group
                        $form->onboarding_step = 'step_3_registration';
                    }
                }

                // Inherit ip_id from group if not set
                if (empty($form->ip_id) && !empty($form->group_id)) {
                    $group = FfsGroup::find($form->group_id);
                    if ($group && $group->ip_id) {
                        $form->ip_id = $group->ip_id;
                    }
                }

                // Final fallback: inherit ip_id from the creating admin user
                if (empty($form->ip_id)) {
                    $adminIpId = \Encore\Admin\Facades\Admin::user()->ip_id ?? null;
                    if ($adminIpId) {
                        $form->ip_id = $adminIpId;
                    }
                }
            } else {
                // Only hash password if provided
                if (!empty($form->password)) {
                    $form->password = bcrypt($form->password);
                } else {
                    unset($form->password);
                }
            }
        });
        
        // Note: Username and password auto-generated from phone number if not provided
        // Default password is the phone number (digits only)
        
        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
