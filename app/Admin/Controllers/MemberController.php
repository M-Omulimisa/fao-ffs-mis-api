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
        $ipId = $this->getAdminIpId();
        $isSuperAdmin = $this->isSuperAdmin();

        $grid->filter(function($filter) use ($ipId, $isSuperAdmin) {
            $filter->disableIdFilter();
            if ($isSuperAdmin) {
                $filter->equal('ip_id', 'Implementing Partner')
                    ->select(\App\Models\ImplementingPartner::getDropdownOptions());
            }
            
            // Group filters
            $filter->equal('group_id', 'FFS Group')->select(
                FfsGroup::where('status', 'Active')
                    ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                    ->orderBy('name')
                    ->pluck('name', 'id')
            );
            
            $filter->equal('is_group_admin', 'Position')->select([
                'Yes' => 'Chairperson',
                'No' => 'Regular Member',
            ]);
            
            // Location filter
            $filter->equal('district_name', 'District')
                ->select($this->northernUgandaDistricts());
            
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
            if ($this->district_name) $parts[] = '<strong>' . e($this->district_name) . '</strong>';
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
        $record = User::findOrFail($id);
        if (!$this->verifyIpAccess($record)) {
            return $this->denyIpAccess();
        }

        $show = new Show($record);
        
        $show->panel()->style('success')->title('Member Profile');

        // Basic Information
        $show->divider('Personal Information');
        $show->field('name', 'Full Name');
        $show->field('sex', 'Gender')->using(['Male' => 'Male', 'Female' => 'Female']);
        $show->field('phone_number', 'Phone Number')->as(function($phone) {
            return $phone ? '<a href="tel:' . $phone . '">' . $phone . '</a>' : 'N/A';
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
        $show->field('district_name', 'District');
        $show->field('village', 'Village');
        
        // Account Information
        $show->divider('Account Status');
        $show->field('status', 'Account Status')->using([
            '1' => '✓ Active',
            '0' => '✗ Inactive',
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

        $form->hidden('user_type')->default('Customer');

        // ── Personal Information ──────────────────────────────────────────
        $form->divider('Personal Information');

        $form->row(function ($row) {
            $row->width(4)->text('first_name', 'First Name')->required();
            $row->width(4)->text('last_name',  'Last Name')->required();
            $row->width(4)->select('sex', 'Gender')
                ->options(['Male' => 'Male', 'Female' => 'Female'])
                ->default('Male')
                ->required();
        });

        $form->row(function ($row) {
            $row->width(3)->date('dob', 'Date of Birth')->help('Format: YYYY-MM-DD');
            $row->width(3)->text('national_id_number', 'National ID (NIN)');
            $row->width(3)->select('marital_status', 'Marital Status')
                ->options([
                    'Single'   => 'Single',
                    'Married'  => 'Married',
                    'Divorced' => 'Divorced',
                    'Widowed'  => 'Widowed',
                ]);
            $row->width(3)->number('household_size', 'Household Size')->min(1);
        });

        $form->row(function ($row) {
            $row->width(4)->text('phone_number', 'Phone Number')
                ->placeholder('e.g. 0771234567 (optional)')
                ->creationRules(['nullable', 'unique:users,phone_number'])
                ->updateRules(['nullable', 'unique:users,phone_number,{{id}}'])
                ->help('Primary contact — leave blank if member has no phone; member code will be used as identifier');
            $row->width(4)->text('phone_number_2', 'Secondary Phone')
                ->placeholder('e.g. 0781234567');
            $row->width(4)->email('email', 'Email Address')
                ->placeholder('e.g. member@example.com');
        });

        $form->row(function ($row) {
            $row->width(6)->select('education_level', 'Education Level')
                ->options([
                    'None'      => 'None',
                    'Primary'   => 'Primary',
                    'Secondary' => 'Secondary',
                    'Tertiary'  => 'Tertiary',
                ]);
            $row->width(6)->text('occupation', 'Occupation');
        });

        // ── Group & Role ─────────────────────────────────────────────────
        $form->divider('Group & Role');

        $form->row(function ($row) {
            $ipId = $this->getAdminIpId();
            $row->width(6)->select('group_id', 'Group')
                ->options(function () use ($ipId) {
                    $query = FfsGroup::where('status', 'Active')
                        ->orderBy('type')->orderBy('name');
                    if ($ipId !== null) {
                        $query->where('ip_id', $ipId);
                    }
                    return $query->get()->mapWithKeys(fn($g) => [
                        $g->id => "[{$g->type}] {$g->name}",
                    ]);
                })
                ->help('Only active groups shown. Group determines the member\'s IP.');
            $row->width(6)->select('status', 'Account Status')
                ->options(['1' => 'Active', '0' => 'Inactive'])
                ->default('1');
        });

        $form->row(function ($row) {
            $row->width(4)->select('is_group_admin',     'Chairperson?')
                ->options(['No' => 'No', 'Yes' => 'Yes'])->default('No');
            $row->width(4)->select('is_group_secretary', 'Secretary?')
                ->options(['No' => 'No', 'Yes' => 'Yes'])->default('No');
            $row->width(4)->select('is_group_treasurer', 'Treasurer?')
                ->options(['No' => 'No', 'Yes' => 'Yes'])->default('No');
        });

        // ── Location ─────────────────────────────────────────────────────
        $form->divider('Location');

        $form->row(function ($row) {
            $row->width(6)->select('district_name', 'District')
                ->options($this->northernUgandaDistricts());
            $row->width(6)->text('village', 'Village');
        });

        // ── Emergency Contact ─────────────────────────────────────────────
        $form->divider('Emergency Contact');

        $form->row(function ($row) {
            $row->width(6)->text('emergency_contact_name',  'Contact Name')
                ->placeholder('Full name of emergency contact');
            $row->width(6)->text('emergency_contact_phone', 'Contact Phone')
                ->placeholder('e.g. 0771234567');
        });

        // ── Additional Information ────────────────────────────────────────
        $form->divider('Additional Information');

        $form->row(function ($row) {
            $row->width(4)->textarea('disabilities', 'Disabilities')
                ->rows(3)->placeholder('Any known disabilities…');
            $row->width(4)->textarea('skills',       'Skills')
                ->rows(3)->placeholder('Key skills or expertise…');
            $row->width(4)->textarea('remarks',      'Remarks')
                ->rows(3)->placeholder('Any additional notes…');
        });

        // ── Account & Security ────────────────────────────────────────────
        $form->divider('Account & Security');

        $form->row(function ($row) use ($form) {
            $row->width(6)->text('username', 'Username')
                ->placeholder('Auto-filled from phone number if blank')
                ->help('Login username — defaults to phone number digits');

            if ($form->isCreating()) {
                $row->width(6)->password('password', 'Password')
                    ->help('Optional — phone number digits used if left blank');
            } else {
                $row->width(6)->password('password', 'Change Password')
                    ->help('Leave blank to keep current password');
            }
        });

        $form->row(function ($row) {
            $roleModel  = config('admin.database.roles_model');
            $rolesQuery = $roleModel::query();
            if (!$this->isSuperAdmin()) {
                $rolesQuery->where('slug', '!=', 'super_admin');
            }
            $row->width(6)->multipleSelect('roles', 'System Roles')
                ->options($rolesQuery->pluck('name', 'id'))
                ->help('Optional — assign portal roles to this member');
        });

        // ── Saving logic ──────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            $form->name = trim($form->first_name . ' ' . $form->last_name);

            if ($form->isCreating()) {
                if (empty($form->username) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                } elseif (empty($form->username)) {
                    $form->username = 'member_' . time();
                }

                $plain = !empty($form->phone_number)
                    ? preg_replace('/[^0-9]/', '', $form->phone_number)
                    : '123456';
                $form->password = empty($form->password)
                    ? bcrypt($plain)
                    : bcrypt($form->password);

                $form->created_by_id    = \Encore\Admin\Facades\Admin::user()->id;
                $form->registered_by_id = \Encore\Admin\Facades\Admin::user()->id;

                if ($form->onboarding_step === 'not_started' || empty($form->onboarding_step)) {
                    if (!empty($form->group_id)) {
                        $activeCycle = \App\Models\Project::where('group_id', $form->group_id)
                            ->where('is_vsla_cycle', 'Yes')
                            ->where('is_active_cycle', 'Yes')
                            ->first();
                        $form->onboarding_step = $activeCycle
                            ? 'step_7_complete'
                            : 'step_5_members';
                        if ($activeCycle) {
                            $form->onboarding_completed_at = now();
                        }
                    } elseif (!empty($form->first_name) && !empty($form->phone_number)) {
                        $form->onboarding_step = 'step_3_registration';
                    }
                }

                if (empty($form->ip_id) && !empty($form->group_id)) {
                    $group = FfsGroup::find($form->group_id);
                    if ($group && $group->ip_id) {
                        $form->ip_id = $group->ip_id;
                    }
                }

                if (empty($form->ip_id)) {
                    $form->ip_id = \Encore\Admin\Facades\Admin::user()->ip_id ?? null;
                }
            } else {
                if (empty($form->username) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                }

                if (!empty($form->password)) {
                    $form->password = bcrypt($form->password);
                } else {
                    unset($form->password);
                }
            }
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
