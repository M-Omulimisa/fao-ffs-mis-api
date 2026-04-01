<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\FfsGroup;
use App\Models\Location;
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

    protected $title = 'System Users';

    /**
     * Make a grid builder.
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->model()->orderBy('id', 'desc');
        $this->applyIpScope($grid);

        $grid->quickSearch('name', 'first_name', 'last_name', 'phone_number', 'email')
            ->placeholder('Search name, phone, email...');

        $ipId = $this->getAdminIpId();
        $isSuperAdmin = $this->isSuperAdmin();

        $grid->filter(function ($filter) use ($ipId, $isSuperAdmin) {
            $filter->disableIdFilter();
            if ($isSuperAdmin) {
                $filter->equal('ip_id', 'Implementing Partner')
                    ->select(\App\Models\ImplementingPartner::getDropdownOptions());
            }

            $filter->like('name', 'Name');
            $filter->like('phone_number', 'Phone');
            $filter->equal('sex', 'Gender')->select(['Male' => 'Male', 'Female' => 'Female']);

            $filter->equal('group_id', 'FFS Group')->select(
                FfsGroup::where('status', 'Active')
                    ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                    ->orderBy('name')->pluck('name', 'id')
            );

            $filter->equal('status', 'Status')->select([
                '1' => 'Active',
                '0' => 'Inactive',
            ]);

            $filter->equal('user_type', 'User Type')->select([
                'Admin' => 'Admin',
                'Customer' => 'Member',
            ]);

            $filter->between('created_at', 'Registered')->date();
        });

        // ── Columns ──
        $grid->column('id', 'ID')->sortable();
        $grid->column('avatar', 'Photo')->image('', 50, 50);

        $grid->column('name', 'Full Name')->display(function () {
            $name = $this->name ?: trim($this->first_name . ' ' . $this->last_name);
            $html = "<strong>{$name}</strong>";

            // Role badges
            if ($this->is_group_admin == 'Yes') {
                $html .= '<br><span class="label label-primary"><i class="fa fa-star"></i> Chairperson</span>';
            } elseif ($this->is_group_secretary == 'Yes') {
                $html .= '<br><span class="label label-info"><i class="fa fa-pencil"></i> Secretary</span>';
            } elseif ($this->is_group_treasurer == 'Yes') {
                $html .= '<br><span class="label label-warning"><i class="fa fa-money"></i> Treasurer</span>';
            }

            return $html;
        })->sortable();

        $grid->column('phone_number', 'Phone')->display(function () {
            $html = '<i class="fa fa-phone text-success"></i> ' . ($this->phone_number ?: '-');
            if ($this->phone_number_2) {
                $html .= '<br><i class="fa fa-phone text-muted"></i> ' . $this->phone_number_2;
            }
            return $html;
        });

        $grid->column('sex', 'Gender')->dot([
            'Male' => 'primary',
            'Female' => 'danger',
        ], 'warning')->sortable();

        $grid->column('group.name', 'FFS Group')->display(function () {
            if (!$this->group) {
                return '<span class="text-muted">Not Assigned</span>';
            }
            $type = $this->group->type;
            $colors = ['FFS' => 'success', 'FBS' => 'primary', 'VSLA' => 'warning', 'Association' => 'info'];
            return '<span class="label label-' . ($colors[$type] ?? 'default') . '">' . $type . '</span><br>'
                 . '<strong>' . $this->group->name . '</strong>';
        });

        $grid->column('ip_id', 'IP')->display(function () {
            if ($this->ip_id) {
                $ip = ImplementingPartner::find($this->ip_id);
                if ($ip) {
                    $name = $ip->short_name ?: $ip->name;
                    return "<span class='label label-primary'>{$name}</span>";
                }
            }
            if ($this->group && $this->group->ip_id) {
                $ip = ImplementingPartner::find($this->group->ip_id);
                if ($ip) {
                    return "<span class='label label-default'>" . ($ip->short_name ?: $ip->name) . "</span>";
                }
            }
            return '<span style="color:#999;">-</span>';
        })->sortable();

        $grid->column('user_type', 'Type')->display(function ($type) {
            $colors = ['Admin' => 'danger', 'Customer' => 'success'];
            $label = $type === 'Customer' ? 'Member' : $type;
            return '<span class="label label-' . ($colors[$type] ?? 'default') . '">' . $label . '</span>';
        })->sortable();

        $grid->column('status', 'Status')->display(function () {
            return $this->status == 1
                ? '<span class="label label-success">Active</span>'
                : '<span class="label label-default">Inactive</span>';
        })->sortable();

        $grid->column('onboarding_step', 'Onboarding')->display(function ($step) {
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
        })->sortable()->hide();

        $grid->column('created_at', 'Registered')->display(function ($date) {
            return \Carbon\Carbon::parse($date)->format('d M Y');
        })->sortable();

        return $grid;
    }

    /**
     * Make a show builder.
     */
    protected function detail($id)
    {
        $record = User::findOrFail($id);
        if (!$this->verifyIpAccess($record)) {
            return $this->denyIpAccess();
        }

        $show = new Show($record);
        $show->panel()->style('success')->title('User Profile');

        $show->field('avatar', 'Photo')->image('', 150, 150);

        $show->divider('Personal Information');
        $show->field('name', 'Full Name');
        $show->field('sex', 'Gender');
        $show->field('phone_number', 'Phone Number');

        $show->divider('Organization');
        $show->field('implementingPartner.name', 'Implementing Partner');
        $show->field('group.name', 'FFS Group');
        $show->field('user_type', 'User Type');
        $show->field('is_group_admin', 'Chairperson')->using(['Yes' => 'Yes', 'No' => 'No']);
        $show->field('is_group_secretary', 'Secretary')->using(['Yes' => 'Yes', 'No' => 'No']);
        $show->field('is_group_treasurer', 'Treasurer')->using(['Yes' => 'Yes', 'No' => 'No']);

        $show->divider('Account');
        $show->field('status', 'Status')->using(['1' => 'Active', '0' => 'Inactive']);
        $show->field('created_at', 'Created');
        $show->field('registered_by_id', 'Registered By')->as(function ($id) {
            if (!$id) return 'N/A';
            $u = User::find($id);
            return $u ? $u->name : 'Unknown';
        });

        return $show;
    }

    /**
     * Make a form builder.
     */
    protected function form()
    {
        $form = new Form(new User());
        $this->addIpFieldToForm($form);

        $form->hidden('user_type')->default('Customer');

        // ── Personal Information ──
        $form->row(function ($row) {
            $row->width(4)->text('first_name', 'First Name')->required();
            $row->width(4)->text('last_name', 'Last Name')->required();
            $row->width(4)->select('sex', 'Gender')
                ->options(['Male' => 'Male', 'Female' => 'Female'])
                ->default('Male')->required();
        });

        $form->row(function ($row) {
            $row->width(3)->text('phone_number', 'Phone Number')
                ->placeholder('e.g. 0771234567 (optional)')
                ->creationRules(['nullable', 'unique:users,phone_number'])
                ->updateRules(['nullable', 'unique:users,phone_number,{{id}}'])
                ->help('Used as login username & default password — leave blank if member has no phone');
            $row->width(3)->email('email', 'Email Address')
                ->placeholder('e.g. user@example.com')
                ->creationRules(['nullable', 'unique:users,email'])
                ->updateRules(['nullable', 'unique:users,email,{{id}}'])
                ->help('Optional — for notifications and password recovery');
            $row->width(3)->date('facilitator_start_date', 'Facilitator Start Date')
                ->help('Date when facilitator started working (for KPI tracking)');
            $row->width(3)->image('avatar', 'Photo')->removable();
        });

        // ── Group & Role ──
        $form->row(function ($row) {
            $ipId = $this->getAdminIpId();
            $row->width(6)->select('group_id', 'FFS Group')->options(function () use ($ipId) {
                $query = FfsGroup::where('status', 'Active')->orderBy('type')->orderBy('name');
                if ($ipId !== null) {
                    $query->where('ip_id', $ipId);
                }
                return $query->get()->mapWithKeys(function ($g) {
                    return [$g->id => "[{$g->type}] {$g->name}"];
                });
            })->help('Only active groups shown');
            $row->width(6)->select('status', 'Account Status')
                ->options(['1' => 'Active', '0' => 'Inactive'])->default('1');
        });

        $form->row(function ($row) {
            $row->width(4)->select('is_group_admin', 'Chairperson?')
                ->options(['No' => 'No', 'Yes' => 'Yes'])->default('No');
            $row->width(4)->select('is_group_secretary', 'Secretary?')
                ->options(['No' => 'No', 'Yes' => 'Yes'])->default('No');
            $row->width(4)->select('is_group_treasurer', 'Treasurer?')
                ->options(['No' => 'No', 'Yes' => 'Yes'])->default('No');
        });

      
        // ── Account & Security ──
        $form->divider('Account & Security');

        $form->row(function ($row) use ($form) {
            $roleModel  = config('admin.database.roles_model', \Encore\Admin\Auth\Database\Role::class);
            $rolesQuery = $roleModel && class_exists($roleModel) ? $roleModel::query() : null;
            if ($rolesQuery) {
                if (!$this->isSuperAdmin()) {
                    $rolesQuery->where('slug', '!=', 'super_admin');
                }
                $row->width(6)->multipleSelect('roles', 'Roles')
                    ->options($rolesQuery->pluck('name', 'id'))
                    ->help('Assign system roles (optional)');
            }

            if ($form->isCreating()) {
                $row->width(6)->password('password', 'Password')
                    ->help('Optional. If blank, phone number is the default password.');
            } else {
                $row->width(6)->password('password', 'Change Password')
                    ->help('Leave blank to keep current password');
            }
        });

        // ── Saving logic ──
        $form->saving(function (Form $form) {
            $first = trim($form->first_name ?? '');
            $last = trim($form->last_name ?? '');
            $fullName = trim($first . ' ' . $last);
            if ($fullName !== '') {
                $form->name = $fullName;
            }
            $adminUser = Admin::user();
            $isSuperAdmin = $this->isSuperAdmin();
            $adminIp = $adminUser->ip_id ?? null;

            // IP control: super admins can assign/change IP; others are pinned to their own IP.
            if ($isSuperAdmin) {
                if (!empty($form->ip_id) && !ImplementingPartner::where('id', $form->ip_id)->exists()) {
                    throw new \Exception('Selected Implementing Partner does not exist.');
                }
            } else {
                if (empty($adminIp)) {
                    throw new \Exception('Your account has no Implementing Partner assigned. Contact super admin.');
                }
                $form->ip_id = $adminIp;
            }

            // Keep super-admin explicit selection intact.
            if ($isSuperAdmin && $form->ip_id !== null && $form->ip_id !== '') {
                $form->input('ip_id', (int) $form->ip_id);
            }

            if ($form->isCreating()) {
                if (empty($form->username) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                } elseif (empty($form->username)) {
                    $form->username = 'user_' . time();
                }

                if (empty($form->password)) {
                    $plain = !empty($form->phone_number) ? preg_replace('/[^0-9]/', '', $form->phone_number) : '123456';
                    $form->password = Hash::make($plain);
                } else {
                    $form->password = Hash::make($form->password);
                }

                $form->created_by_id = Admin::user()->id;
                $form->registered_by_id = Admin::user()->id;

                // Smart onboarding
                if ($form->onboarding_step === 'not_started' || empty($form->onboarding_step)) {
                    if (!empty($form->group_id)) {
                        $activeCycle = \App\Models\Project::where('group_id', $form->group_id)
                            ->where('is_vsla_cycle', 'Yes')->where('is_active_cycle', 'Yes')->first();
                        $form->onboarding_step = $activeCycle ? 'step_7_complete' : 'step_5_members';
                        if ($activeCycle) $form->onboarding_completed_at = now();
                    } elseif (!empty($form->first_name) && !empty($form->phone_number)) {
                        $form->onboarding_step = 'step_3_registration';
                    }
                }

                // Inherit ip_id from group if not set explicitly
                if (empty($form->ip_id) && !empty($form->group_id)) {
                    $group = FfsGroup::find($form->group_id);
                    if ($group && $group->ip_id) $form->ip_id = $group->ip_id;
                }

                // Fallback: inherit ip_id from creating admin for non-super-admins.
                if (empty($form->ip_id) && !$isSuperAdmin && $adminIp) {
                    $form->ip_id = $adminIp;
                }
            } else {
                if (empty($form->username) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                } elseif (empty($form->username)) {
                    unset($form->username); // keep existing DB value, don't blank it
                }

                // Protect password: only hash if user typed a NEW password
                if ($form->password && $form->model()->password != $form->password) {
                    $form->password = Hash::make($form->password);
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
