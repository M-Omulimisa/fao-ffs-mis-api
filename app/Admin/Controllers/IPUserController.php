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
use Illuminate\Support\Facades\Log;

/**
 * IPUserController — manages all admin-panel users across the hierarchy.
 *
 * Access tiers:
 *   Super Admin  → all users (any user_type, any IP)
 *   IP Manager   → only users whose ip_id matches their own
 *   Facilitator  → only themselves (read +  edit own profile)
 */
class IPUserController extends AdminController
{
    use IpScopeable;

    protected $title = 'User Management';

    // ─────────────────────────────────────────────────────────────────────────
    // GRID
    // ─────────────────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new User());

        $currentAdmin = Admin::user();
        $isSuperAdmin = $this->isSuperAdmin();
        $isFacilitator = !$isSuperAdmin
            && $this->userHasRoleSlug($currentAdmin, 'facilitator');
        $ipId = $this->getAdminIpId();

        // ── Base scope ────────────────────────────────────────────────────
        $grid->model()->orderBy('id', 'desc');

        if ($isFacilitator) {
            $grid->model()->where('id', $currentAdmin->id);
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });
        } elseif (!$isSuperAdmin && $ipId) {
            $grid->model()->where('ip_id', $ipId);
        }

        // ── Search ────────────────────────────────────────────────────────
        $grid->quickSearch('name', 'first_name', 'last_name', 'phone_number', 'email')
            ->placeholder('Search name, phone or email…');

        // ── Filters ───────────────────────────────────────────────────────
        $grid->filter(function ($filter) use ($isSuperAdmin) {
            $filter->disableIdFilter();

            if ($isSuperAdmin) {
                $filter->equal('ip_id', 'Implementing Partner')
                    ->select(ImplementingPartner::getDropdownOptions());
            }

            $filter->like('name',         'Name');
            $filter->like('phone_number', 'Phone');
            $filter->equal('sex', 'Gender')->select(['Male' => 'Male', 'Female' => 'Female']);
            $filter->equal('user_type', 'User Type')->select([
                'Admin'    => 'Admin',
                'Customer' => 'Member / Facilitator',
            ]);
            $filter->equal('status', 'Status')->select(['1' => 'Active', '0' => 'Inactive']);
        });

        // ── Columns ───────────────────────────────────────────────────────
        $grid->column('id', 'ID')->sortable();

        $grid->column('name', 'User')->display(function () {
            $name = e($this->name ?: trim("{$this->first_name} {$this->last_name}"));
            return "<strong>{$name}</strong>";
        })->sortable();

        $grid->column('phone_number', 'Phone');
        $grid->column('email',        'Email');

        $grid->column('roles_list', 'Roles')->display(function () {
            try {
                $roles = $this->roles()->get(['name']);
                if ($roles->isEmpty()) return '<span class="label label-default">—</span>';
                return $roles->map(function ($r) {
                    return '<span class="label label-info">' . e($r->name) . '</span>';
                })->implode(' ');
            } catch (\Throwable $e) {
                return '—';
            }
        });

        $grid->column('user_type', 'Type')->display(function ($v) {
            $colour = $v === 'Admin' ? 'primary' : 'default';
            return '<span class="label label-' . $colour . '">' . e($v) . '</span>';
        });

        if ($isSuperAdmin) {
            $grid->column('ip_id', 'Partner')->display(function ($id) {
                if (!$id) return '—';
                $ip = ImplementingPartner::find($id);
                return $ip ? e($ip->short_name ?: $ip->name) : "IP #{$id}";
            });
        }

        $grid->column('status', 'Status')->display(function ($v) {
            return $v == 1
                ? '<span class="label label-success">Active</span>'
                : '<span class="label label-default">Inactive</span>';
        })->sortable();

        $grid->column('created_at', 'Created')->date('d M Y')->sortable();

        return $grid;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────────

    protected function detail($id)
    {
        $user = User::findOrFail($id);

        if (!$this->verifyIpAccess($user)) {
            // Facilitators may only view themselves
            $currentAdmin = Admin::user();
            if (!$this->isSuperAdmin() && (int) $user->id !== (int) $currentAdmin->id) {
                return $this->denyIpAccess();
            }
        }

        $show = new Show($user);

        $show->field('id',           'ID');
        $show->divider();
        $show->field('first_name',   'First Name');
        $show->field('last_name',    'Last Name');
        $show->field('name',         'Full Name');
        $show->field('sex',          'Gender');
        $show->field('phone_number', 'Phone');
        $show->field('email',        'Email');
        $show->field('username',     'Username');
        $show->field('user_type',    'User Type');

        $show->divider();
        $show->field('ip_id', 'Implementing Partner')->as(function ($id) {
            $ip = ImplementingPartner::find($id);
            return $ip ? "{$ip->name} ({$ip->short_name})" : '—';
        });
        $show->field('status', 'Status')->as(fn($v) => $v == 1 ? 'Active' : 'Inactive');
        $show->field('facilitator_start_date', 'Facilitator Since');
        $show->field('created_at',   'Created At');

        return $show;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new User());

        // ── Implementing Partner ──────────────────────────────────────────
        $this->addIpFieldToForm($form);

        // ── Personal Information ──────────────────────────────────────────
        $form->divider('Personal Information');

        $form->row(function ($row) {
            $row->width(4)->text('first_name', 'First Name')->required();
            $row->width(4)->text('last_name',  'Last Name')->required();
            $row->width(4)->select('sex', 'Gender')
                ->options(['Male' => 'Male', 'Female' => 'Female'])
                ->default('Male');
        });

        $form->row(function ($row) {
            $row->width(6)->text('phone_number', 'Phone Number')
                ->placeholder('e.g. 0771234567 (optional)')
                ->creationRules(['nullable', 'unique:users,phone_number'])
                ->updateRules(['nullable', 'unique:users,phone_number,{{id}}']);
            $row->width(6)->email('email', 'Email Address')
                ->placeholder('e.g. user@example.com');
        });

        // ── User Type & Status ────────────────────────────────────────────
        $form->divider('Account Settings');

        $form->row(function ($row) {
            $row->width(4)->select('user_type', 'User Type')
                ->options(['Admin' => 'Admin', 'Customer' => 'Member / Facilitator'])
                ->default('Admin')
                ->required();
            $row->width(4)->select('status', 'Account Status')
                ->options(['1' => 'Active', '0' => 'Inactive'])
                ->default('1')
                ->required();
            $row->width(4)->date('facilitator_start_date', 'Facilitator Start Date')
                ->help('Set only for facilitators');
        });

        // ── Roles ─────────────────────────────────────────────────────────
        $form->row(function ($row) {
            $roleModel  = config('admin.database.roles_model');
            $rolesQuery = $roleModel::query();
            if (!$this->isSuperAdmin()) {
                $rolesQuery->where('slug', '!=', 'super_admin');
            }
            $row->width(8)->multipleSelect('roles', 'Assigned Roles')
                ->options($rolesQuery->pluck('name', 'id'))
                ->help('Select all roles that apply to this user');
        });

        // ── Account & Security ────────────────────────────────────────────
        $form->divider('Account & Security');

        $form->row(function ($row) use ($form) {
            $row->width(6)->text('username', 'Username')
                ->placeholder('Auto-filled from phone if blank');

            if ($form->isCreating()) {
                $row->width(6)->password('password', 'Password')
                    ->help('Optional — phone digits used if blank');
            } else {
                $row->width(6)->password('password', 'Change Password')
                    ->help('Leave blank to keep current password');
            }
        });

        // ── Saving logic ──────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            $form->name = trim($form->first_name . ' ' . $form->last_name);

            if ($form->isCreating()) {
                if (empty($form->username) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                } elseif (empty($form->username)) {
                    $form->username = 'user_' . time();
                }

                $plain = !empty($form->phone_number)
                    ? preg_replace('/[^0-9]/', '', $form->phone_number)
                    : '123456';
                $form->password = empty($form->password)
                    ? bcrypt($plain)
                    : bcrypt($form->password);

                $form->created_by_id    = Admin::user()->id;
                $form->registered_by_id = Admin::user()->id;

                if (empty($form->ip_id)) {
                    $form->ip_id = Admin::user()->ip_id ?? null;
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

    // ─────────────────────────────────────────────────────────────────────────
    // SEND CREDENTIALS
    // ─────────────────────────────────────────────────────────────────────────

    public function sendCredentials($id)
    {
        $user = User::findOrFail($id);

        if (!$this->verifyIpAccess($user)) {
            return $this->denyIpAccess();
        }

        if (empty($user->phone_number)) {
            admin_toastr('User has no phone number on file.', 'error');
            return redirect()->back();
        }

        $firstName = $user->first_name ?: explode(' ', $user->name)[0];
        $username  = $user->username ?: $user->phone_number;
        $password  = preg_replace('/[^0-9]/', '', $user->phone_number);

        $message  = "FAO FFS-MIS — Login Credentials\n\n";
        $message .= "Dear {$firstName},\n";
        $message .= "Username: {$username}\n";
        $message .= "Password: {$password}\n\n";
        $message .= "Download the FAO FFS-MIS app or visit the web portal.";

        try {
            \App\Models\Utils::send_sms($user->phone_number, $message);
            admin_toastr("Credentials sent to {$user->name} ({$user->phone_number})", 'success');
            Log::info("IPUserController: credentials SMS sent to #{$user->id}");
        } catch (\Exception $e) {
            admin_toastr('Failed to send SMS: ' . $e->getMessage(), 'error');
            Log::error("IPUserController: SMS failed #{$user->id}: " . $e->getMessage());
        }

        return redirect()->back();
    }
}
