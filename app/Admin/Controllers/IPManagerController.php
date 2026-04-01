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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IPManagerController extends AdminController
{
    use IpScopeable;

    protected $title = 'IP Managers';

    /**
     * Get IDs of all users who have the ip_manager role.
     */
    private function ipManagerIds()
    {
        return DB::table('admin_role_users')
            ->join('admin_roles', 'admin_roles.id', '=', 'admin_role_users.role_id')
            ->where('admin_roles.slug', 'ip_manager')
            ->distinct()
            ->pluck('admin_role_users.user_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GRID
    // ─────────────────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new User());

        $currentAdmin = Admin::user();
        $isSuperAdmin = $this->isSuperAdmin();

        // Only super admins and ip_managers can access this section
        if (!$isSuperAdmin && !$this->userHasRoleSlug($currentAdmin, 'ip_manager')) {
            admin_toastr('Access denied: only Super Admins and IP Managers can view this section.', 'error');
            return $grid;
        }

        // ── Base scope: only users with ip_manager role ──
        $ids = $this->ipManagerIds();
        $grid->model()
            ->whereIn('id', $ids)
            ->orderBy('id', 'desc');

        // ── IP scoping ──
        if (!$isSuperAdmin) {
            $ipId = $this->getAdminIpId();
            if ($ipId) {
                $grid->model()->where('ip_id', $ipId);
            }
            // Non-super-admins cannot create or delete IP managers
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });
        }

        // ── Search ──
        $grid->quickSearch('name', 'first_name', 'last_name', 'phone_number', 'email')
            ->placeholder('Search name, phone or email…');

        // ── Filters ──
        $grid->filter(function ($filter) use ($isSuperAdmin) {
            $filter->disableIdFilter();

            if ($isSuperAdmin) {
                $filter->equal('ip_id', 'Implementing Partner')
                    ->select(ImplementingPartner::getDropdownOptions());
            }

            $filter->like('name', 'Name');
            $filter->like('phone_number', 'Phone');
            $filter->equal('sex', 'Gender')->select(['Male' => 'Male', 'Female' => 'Female']);
            $filter->equal('status', 'Status')->select(['1' => 'Active', '0' => 'Inactive']);
        });

        // ── Columns ──
        $grid->column('id', 'ID')->sortable();

        $grid->column('name', 'Name')->display(function () {
            $name = e($this->name ?: trim("{$this->first_name} {$this->last_name}"));
            return "<strong>{$name}</strong>";
        })->sortable();

        $grid->column('phone_number', 'Phone');
        $grid->column('email', 'Email');
        $grid->column('sex', 'Gender')->hide();

        $grid->column('ip_id', 'Partner')->display(function ($id) {
            if (!$id) return '<span class="text-muted">—</span>';
            $ip = ImplementingPartner::find($id);
            return $ip ? '<span class="label label-primary">' . e($ip->short_name ?: $ip->name) . '</span>' : "IP #{$id}";
        });

        $grid->column('district_name', 'District')->display(fn($v) => $v ?: '—');

        $grid->column('status', 'Status')->display(function ($v) {
            return $v == 1
                ? '<span class="label label-success">Active</span>'
                : '<span class="label label-default">Inactive</span>';
        })->sortable();

        $grid->column('last_seen', 'Last Seen')->display(function ($v) {
            return $v ? \Carbon\Carbon::parse($v)->diffForHumans() : '—';
        })->sortable();

        $grid->column('created_at', 'Created')->date('d M Y')->sortable();

        // ── Row actions ──
        $grid->actions(function ($actions) use ($isSuperAdmin) {
            if ($isSuperAdmin) {
                $row = $actions->row;
                $id = $row->id;

                // Send credentials action
                $url = admin_url("ip-managers/{$id}/send-credentials");
                $actions->append("<a class='btn btn-xs btn-default' href='{$url}' title='Send Credentials SMS'>"
                    . "<i class='fa fa-envelope'></i></a> ");

                // Reset password action
                $url = admin_url("ip-managers/{$id}/reset-password");
                $actions->append("<a class='btn btn-xs btn-warning' href='{$url}' title='Reset Password'>"
                    . "<i class='fa fa-key'></i></a> ");
            }
        });

        return $grid;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────────

    private function showDetailSection(Show $show, string $label, string $icon = ''): void
    {
        $show->divider();
        $ico  = $icon ? "<i class='fa fa-{$icon} fa-fw'></i> " : '';
        $html = "<div style='margin:4px 0 2px;padding-bottom:4px;border-bottom:1px solid #ddd;'>"
              . "<span style='font-size:11px;font-weight:700;text-transform:uppercase;"
              . "letter-spacing:.6px;color:#666;'>{$ico}" . htmlspecialchars($label, ENT_QUOTES) . "</span></div>";
        static $idx = 0;
        $show->field('_ds_' . (++$idx), '')->as(fn() => $html)->unescape();
    }

    protected function detail($id)
    {
        $manager = User::findOrFail($id);

        if (!$this->verifyIpAccess($manager)) {
            return $this->denyIpAccess();
        }

        // Pre-load stats: facilitators and groups under this IP
        $ipId = $manager->ip_id;
        $facilitatorCount = 0;
        $groupCount = 0;
        $memberCount = 0;
        $groups = collect();

        if ($ipId) {
            $facilitatorCount = DB::table('users')
                ->where('ip_id', $ipId)
                ->whereIn('id', function ($q) {
                    $q->select('facilitator_id')->from('ffs_groups')->whereNotNull('facilitator_id');
                })
                ->count();

            $groups = \App\Models\FfsGroup::where('ip_id', $ipId)
                ->withCount('members')
                ->orderBy('status')->orderBy('name')
                ->get();

            $groupCount = $groups->count();
            $memberCount = $groups->sum('members_count');
        }

        $show = new Show($manager);
        $show->panel()->style('primary')
            ->title(e($manager->name ?: trim($manager->first_name . ' ' . $manager->last_name)));

        // ── Personal Information ──
        $show->field('name', 'Full Name')->as(function ($v) {
            $n = $v ?: trim($this->first_name . ' ' . $this->last_name);
            return "<strong style='font-size:15px;'>" . e($n) . "</strong>";
        })->unescape();
        $show->field('sex', 'Gender')->as(fn($v) => $v ?: '-');
        $show->field('phone_number', 'Phone')->as(function ($v) {
            if (!$v) return '-';
            $clean = preg_replace('/[^0-9+]/', '', $v);
            return "<a href='tel:{$clean}' style='color:#337ab7;'>"
                . "<i class='fa fa-phone'></i> " . e($v) . "</a>";
        })->unescape();
        $show->field('email', 'Email')->as(function ($v) {
            if (!$v) return '-';
            return "<a href='mailto:" . e($v) . "' style='color:#337ab7;'>"
                . "<i class='fa fa-envelope'></i> " . e($v) . "</a>";
        })->unescape();
        $show->field('national_id_number', 'National ID (NIN)')->as(fn($v) => $v ?: '-');
        $show->field('username', 'Login Username')->as(fn($v) => $v ?: '-');

        // ── Location ──
        $this->showDetailSection($show, 'Location', 'map-marker');
        $show->field('district_name', 'District')->as(fn($v) => $v ? ucwords(strtolower($v)) : '-');
        $show->field('village', 'Village')->as(fn($v) => $v ? ucwords(strtolower($v)) : '-');

        // ── Work Assignment ──
        $this->showDetailSection($show, 'Work Assignment', 'briefcase');
        $show->field('ip_id', 'Implementing Partner')->as(function ($ipId) {
            if (!$ipId) return '-';
            $ip = ImplementingPartner::find($ipId);
            if (!$ip) return '-';
            $url   = admin_url("implementing-partners/{$ip->id}");
            $short = $ip->short_name ? " <span class='text-muted'>({$ip->short_name})</span>" : '';
            return "<a href='{$url}' style='color:#337ab7;'>"
                . "<i class='fa fa-external-link fa-fw'></i> " . e($ip->name) . "</a>" . $short;
        })->unescape();
        $show->field('position', 'Position')->as(fn($v) => $v ?: '-');
        $show->field('department', 'Department')->as(fn($v) => $v ?: '-');
        $show->field('contract_type', 'Contract Type')->as(fn($v) => $v ?: '-');
        $show->field('status', 'Account Status')->as(function ($v) {
            return $v == 1
                ? "<span class='label label-success'><i class='fa fa-check'></i> Active</span>"
                : "<span class='label label-default'>Inactive</span>";
        })->unescape();

        // ── IP Portfolio Overview ──
        $this->showDetailSection($show, 'IP Portfolio Overview', 'bar-chart');
        $show->field('_stat_facilitators', 'Facilitators Under IP')->as(function () use ($facilitatorCount) {
            return "<span style='font-size:22px;font-weight:700;color:#3c8dbc;'>{$facilitatorCount}</span>"
                . " <span class='text-muted'>facilitator" . ($facilitatorCount != 1 ? 's' : '') . "</span>";
        })->unescape();
        $show->field('_stat_groups', 'Groups Under IP')->as(function () use ($groupCount) {
            return "<span style='font-size:22px;font-weight:700;color:#05179F;'>{$groupCount}</span>"
                . " <span class='text-muted'>group" . ($groupCount != 1 ? 's' : '') . "</span>";
        })->unescape();
        $show->field('_stat_members', 'Total Members')->as(function () use ($memberCount) {
            return "<span style='font-size:22px;font-weight:700;color:#00a65a;'>{$memberCount}</span>"
                . " <span class='text-muted'>member" . ($memberCount != 1 ? 's' : '') . " across all groups</span>";
        })->unescape();

        // ── Audit ──
        $this->showDetailSection($show, 'Audit', 'clock-o');
        $show->field('created_at', 'Registered')->as(fn($d) => $d ? date('d M Y H:i', strtotime($d)) : '-');
        $show->field('updated_at', 'Last Updated')->as(fn($d) => $d ? date('d M Y H:i', strtotime($d)) : '-');
        $show->field('last_seen', 'Last Login')->as(fn($d) => $d ? date('d M Y H:i', strtotime($d)) : 'Never');

        return $show;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new User());

        // Only super admins can create/edit IP managers
        if (!$this->isSuperAdmin()) {
            admin_toastr('Only Super Admins can create or edit IP Managers.', 'error');
            return $form;
        }

        $this->addIpFieldToForm($form);

        // IP Managers are Admin-type users
        $form->hidden('user_type')->default('Admin');

        // ── Personal information ──
        $form->row(function ($row) {
            $row->width(4)->text('first_name', 'First Name')->required();
            $row->width(4)->text('last_name', 'Last Name')->required();
            $row->width(4)->select('sex', 'Gender')
                ->options(['Male' => 'Male', 'Female' => 'Female'])
                ->default('Male')
                ->required();
        });

        $form->row(function ($row) {
            $row->width(6)->text('phone_number', 'Phone Number')
                ->placeholder('e.g. 0771234567')
                ->creationRules(['required', 'unique:users,phone_number'])
                ->updateRules(['required', 'unique:users,phone_number,{{id}}']);
            $row->width(6)->email('email', 'Email Address')
                ->placeholder('e.g. manager@example.com')
                ->creationRules(['nullable', 'unique:users,email'])
                ->updateRules(['nullable', 'unique:users,email,{{id}}']);
        });

        $form->row(function ($row) {
            $row->width(4)->text('national_id_number', 'National ID (NIN)');
        });

        // ── Location ──
        $form->divider('Location');

        $form->row(function ($row) {
            $row->width(6)->select('district_name', 'District')
                ->options($this->northernUgandaDistricts());
            $row->width(6)->text('village', 'Village');
        });

        // ── Work information ──
        $form->divider('Work Information');

        $form->row(function ($row) {
            $row->width(4)->text('position', 'Position')
                ->placeholder('e.g. IP Coordinator');
            $row->width(4)->text('department', 'Department')
                ->placeholder('e.g. Programme Management');
            $row->width(4)->select('contract_type', 'Contract Type')
                ->options(['Full Time' => 'Full Time', 'Part Time' => 'Part Time']);
        });

        $form->row(function ($row) {
            $row->width(4)->date('facilitator_start_date', 'Start Date')
                ->help('Date the manager joined the programme');
            $row->width(4)->select('status', 'Account Status')
                ->options(['1' => 'Active', '0' => 'Inactive'])
                ->default('1')
                ->required();
        });

        // ── Account & Security ──
        $form->divider('Account & Security');

        $form->row(function ($row) use ($form) {
            $row->width(6)->text('username', 'Username')
                ->placeholder('Auto-filled from phone number if blank');

            if ($form->isCreating()) {
                $row->width(6)->password('password', 'Password')
                    ->help('Optional — phone number digits used if left blank');
            } else {
                $row->width(6)->password('password', 'Change Password')
                    ->help('Leave blank to keep current password');
            }
        });

        // ── Saving logic ──
        $form->saving(function (Form $form) {
            $form->user_type = 'Admin';

            // Build name safely
            $first = trim($form->first_name ?? '');
            $last  = trim($form->last_name ?? '');
            if ($first !== '' && $last !== '') {
                $form->name = trim($first . ' ' . $last);
            } elseif ($first !== '') {
                $form->name = $first;
            } elseif ($last !== '') {
                $form->name = $last;
            }

            if ($form->isCreating()) {
                if (empty($form->username) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                } elseif (empty($form->username)) {
                    $form->username = 'ipm_' . time();
                }

                $plain = !empty($form->phone_number)
                    ? preg_replace('/[^0-9]/', '', $form->phone_number)
                    : '123456';
                $form->password = empty($form->password)
                    ? bcrypt($plain)
                    : bcrypt($form->password);

                $form->created_by_id    = Admin::user()->id;
                $form->registered_by_id = Admin::user()->id;

                // IP Managers skip onboarding
                $form->onboarding_step = 'step_7_complete';
                $form->onboarding_completed_at = now();
            } else {
                if (empty($form->username) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                } elseif (empty($form->username)) {
                    unset($form->username);
                }

                // Protect fields from being wiped on update
                if (empty(trim((string) ($form->email ?? '')))) {
                    unset($form->email);
                }
                if (empty(trim((string) ($form->phone_number ?? '')))) {
                    unset($form->phone_number);
                }

                if (!empty($form->password)) {
                    $form->password = bcrypt($form->password);
                } else {
                    unset($form->password);
                }
            }
        });

        // After saving, assign the ip_manager role
        $form->saved(function (Form $form) {
            $userId = $form->model()->id;

            // Get the ip_manager role ID
            $role = DB::table('admin_roles')->where('slug', 'ip_manager')->first();
            if ($role) {
                // Ensure the role assignment exists (idempotent)
                $exists = DB::table('admin_role_users')
                    ->where('user_id', $userId)
                    ->where('role_id', $role->id)
                    ->exists();

                if (!$exists) {
                    DB::table('admin_role_users')->insert([
                        'user_id' => $userId,
                        'role_id' => $role->id,
                    ]);
                    Log::info("IPManagerController: assigned ip_manager role to user #{$userId}");
                }
            }
        });

        return $form;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CUSTOM ACTIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send login credentials via SMS.
     */
    public function sendCredentials($id)
    {
        $manager = User::findOrFail($id);

        if (!$this->verifyIpAccess($manager)) {
            return $this->denyIpAccess();
        }

        $email = $manager->email ?: $manager->username;
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            admin_toastr('IP Manager has no valid email address — cannot send credentials.', 'error');
            return redirect()->back();
        }

        $password = preg_replace('/[^0-9]/', '', $manager->phone_number ?: '') ?: '123456';

        try {
            \App\Models\Utils::send_credentials_email($manager, $password, 'IP Manager');
            admin_toastr("Credentials emailed to {$manager->name} ({$email})", 'success');
            Log::info("IPManagerController: credentials email sent to #{$manager->id}");
        } catch (\Exception $e) {
            admin_toastr('Failed to send email: ' . $e->getMessage(), 'error');
            Log::error("IPManagerController: credentials email failed for #{$manager->id}: " . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Reset password to phone number digits.
     */
    public function resetPassword($id)
    {
        if (!$this->isSuperAdmin()) {
            admin_toastr('Only Super Admins can reset passwords.', 'error');
            return redirect()->back();
        }

        $manager = User::findOrFail($id);

        $plain = !empty($manager->phone_number)
            ? preg_replace('/[^0-9]/', '', $manager->phone_number)
            : '123456';

        DB::table('users')->where('id', $manager->id)->update([
            'password' => bcrypt($plain),
        ]);

        admin_toastr("Password reset for {$manager->name}. New password: phone digits.", 'success');
        Log::info("IPManagerController: password reset for user #{$manager->id}");

        return redirect()->back();
    }
}
