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
        $grid->disableBatchActions();

        $grid->quickSearch('name', 'first_name', 'last_name', 'phone_number', 'email')
            ->placeholder('Search name, phone, email...');

        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });

        // ── Filters ──
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
        $show->field('dob', 'Date of Birth')->as(function ($date) {
            if (!$date) return 'N/A';
            return date('d M Y', strtotime($date)) . ' (' . \Carbon\Carbon::parse($date)->age . ' yrs)';
        });
        $show->field('marital_status', 'Marital Status');
        $show->field('education_level', 'Education Level');
        $show->field('occupation', 'Occupation');

        $show->divider('Contact');
        $show->field('phone_number', 'Primary Phone');
        $show->field('phone_number_2', 'Secondary Phone');
        $show->field('email', 'Email');

        $show->divider('Organization');
        $show->field('implementingPartner.name', 'Implementing Partner');
        $show->field('group.name', 'FFS Group');
        $show->field('user_type', 'User Type');
        $show->field('is_group_admin', 'Chairperson')->using(['Yes' => 'Yes', 'No' => 'No']);
        $show->field('is_group_secretary', 'Secretary')->using(['Yes' => 'Yes', 'No' => 'No']);
        $show->field('is_group_treasurer', 'Treasurer')->using(['Yes' => 'Yes', 'No' => 'No']);

        $show->divider('Location');
        $show->field('district_id', 'District')->as(function () {
            $loc = Location::find($this->district_id);
            return $loc ? $loc->name : 'N/A';
        });
        $show->field('subcounty_id', 'Subcounty')->as(function () {
            $loc = Location::find($this->subcounty_id);
            return $loc ? $loc->name : 'N/A';
        });
        $show->field('village', 'Village');
        $show->field('address', 'Address');

        $show->divider('Household');
        $show->field('national_id_number', 'National ID (NIN)');
        $show->field('household_size', 'Household Size');
        $show->field('father_name', "Father's Name");
        $show->field('mother_name', "Mother's Name");
        $show->field('emergency_contact_name', 'Emergency Contact');
        $show->field('emergency_contact_phone', 'Emergency Contact Phone');

        $show->divider('Account');
        $show->field('username', 'Username');
        $show->field('status', 'Status')->using(['1' => 'Active', '0' => 'Inactive']);
        $show->field('onboarding_step', 'Onboarding Step');
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
            $row->width(4)->radio('sex', 'Gender')
                ->options(['Male' => 'Male', 'Female' => 'Female'])
                ->default('Male')->required();
        });

        $form->row(function ($row) {
            $row->width(4)->date('dob', 'Date of Birth')->help('Optional');
            $row->width(4)->select('marital_status', 'Marital Status')->options([
                'Single' => 'Single', 'Married' => 'Married', 'Divorced' => 'Divorced',
                'Widowed' => 'Widowed', 'Separated' => 'Separated',
            ])->default('Single');
            $row->width(4)->select('education_level', 'Education Level')->options([
                'None' => 'No Formal Education', 'Primary' => 'Primary', 'O-Level' => 'O-Level',
                'A-Level' => 'A-Level', 'Certificate' => 'Certificate', 'Diploma' => 'Diploma',
                'Degree' => 'Degree', 'Masters' => 'Masters', 'PhD' => 'PhD',
            ]);
        });

        $form->row(function ($row) {
            $row->width(6)->text('occupation', 'Occupation')->default('Farmer');
            $row->width(6)->image('avatar', 'Photo')->removable();
        });

        // ── Contact ──
        $form->row(function ($row) {
            $row->width(4)->text('phone_number', 'Primary Phone')
                ->placeholder('e.g. 0771234567')
                ->creationRules(['required', 'unique:users,phone_number'])
                ->updateRules(['required', 'unique:users,phone_number,{{id}}'])
                ->help('Used as login username & default password');
            $row->width(4)->text('phone_number_2', 'Alternative Phone');
            $row->width(4)->email('email', 'Email');
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
            $row->width(6)->radio('status', 'Account Status')
                ->options(['1' => 'Active', '0' => 'Inactive'])->default('1');
        });

        $form->row(function ($row) {
            $row->width(4)->radio('is_group_admin', 'Chairperson?')
                ->options(['Yes' => 'Yes', 'No' => 'No'])->default('No');
            $row->width(4)->radio('is_group_secretary', 'Secretary?')
                ->options(['Yes' => 'Yes', 'No' => 'No'])->default('No');
            $row->width(4)->radio('is_group_treasurer', 'Treasurer?')
                ->options(['Yes' => 'Yes', 'No' => 'No'])->default('No');
        });

        // ── Location ──
        $form->row(function ($row) {
            $row->width(3)->select('district_id', 'District')->options(function () {
                return Location::where('parent', 0)->orderBy('name')->pluck('name', 'id');
            });
            $row->width(3)->select('subcounty_id', 'Subcounty')->options(function () {
                return Location::where('parent', '>', 0)
                    ->whereHas('parent_location', fn($q) => $q->where('parent', 0))
                    ->orderBy('name')->pluck('name', 'id');
            });
            $row->width(3)->select('parish_id', 'Parish')->options(function () {
                return Location::whereHas('parent_location', fn($q) => $q->where('parent', '>', 0))
                    ->orderBy('name')->pluck('name', 'id');
            });
            $row->width(3)->text('village', 'Village');
        });

        $form->text('address', 'Home Address');

        // ── Household & Identity ──
        $form->divider('Household & Identity');
        $form->row(function ($row) {
            $row->width(4)->text('national_id_number', 'National ID (NIN)');
            $row->width(4)->number('household_size', 'Household Size')->default(1)->min(1);
            $row->width(4)->select('onboarding_step', 'Onboarding Step')->options([
                'not_started'         => '0 - Not Started',
                'step_1_welcome'      => '1 - Welcome Seen',
                'step_2_terms'        => '2 - Terms Accepted',
                'step_3_registration' => '3 - Registered',
                'step_4_group'        => '4 - Group Created',
                'step_5_members'      => '5 - Members Registered',
                'step_6_cycle'        => '6 - Cycle Configured',
                'step_7_complete'     => '7 - Onboarding Complete',
            ])->default('not_started');
        });

        $form->row(function ($row) {
            $row->width(6)->text('father_name', "Father's Name");
            $row->width(6)->text('mother_name', "Mother's Name");
        });

        $form->row(function ($row) {
            $row->width(6)->text('emergency_contact_name', 'Emergency Contact Name');
            $row->width(6)->mobile('emergency_contact_phone', 'Emergency Contact Phone');
        });

        $form->row(function ($row) {
            $row->width(6)->textarea('skills', 'Skills')->rows(2);
            $row->width(6)->textarea('disabilities', 'Special Needs')->rows(2);
        });

        $form->textarea('remarks', 'Remarks')->rows(2);

        // ── Account & Security ──
        $form->divider('Account & Security');

        $form->row(function ($row) use ($form) {
            $roleModel = config('admin.database.roles_model');
            $rolesQuery = $roleModel::query();
            if (!$this->isSuperAdmin()) {
                $rolesQuery->where('slug', '!=', 'super_admin');
            }
            $row->width(6)->multipleSelect('roles', 'Roles')
                ->options($rolesQuery->pluck('name', 'id'))
                ->help('Assign system roles (optional)');

            if ($form->isCreating()) {
                $row->width(6)->password('password', 'Password')
                    ->help('Optional. If blank, phone number is the default password.');
            } else {
                $row->width(6)->password('password', 'Change Password')
                    ->help('Leave blank to keep current password');
            }
        });

        if ($form->isCreating()) {
            $form->text('username', 'Username (optional)');
        }

        // ── Saving logic ──
        $form->saving(function (Form $form) {
            $form->name = trim($form->first_name . ' ' . $form->last_name);

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

                // Inherit ip_id from group if not set
                if (empty($form->ip_id) && !empty($form->group_id)) {
                    $group = FfsGroup::find($form->group_id);
                    if ($group && $group->ip_id) $form->ip_id = $group->ip_id;
                }

                // Fallback: inherit ip_id from creating admin
                if (empty($form->ip_id)) {
                    $adminIp = Admin::user()->ip_id ?? null;
                    if ($adminIp) $form->ip_id = $adminIp;
                }
            } else {
                if (!empty($form->password)) {
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
