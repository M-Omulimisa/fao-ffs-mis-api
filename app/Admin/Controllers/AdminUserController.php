<?php

namespace App\Admin\Controllers;

use App\Models\ImplementingPartner;
use App\Models\User;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * AdminUserController — 360° management of admin portal accounts.
 *
 * Scope:  Every user who has at least one entry in admin_role_users
 *         (i.e. anyone who can log into the /admin portal).
 *
 * Access tiers:
 *   Super Admin  → all admin users across all IPs; may manage super_admin role
 *   IP Manager   → only admin users whose ip_id matches their own; cannot touch super_admin role
 *   Others       → access denied at the gate
 *
 * Custom routes (must be registered BEFORE the resource):
 *   GET  admin-users/{id}/activate         → activate()
 *   GET  admin-users/{id}/deactivate       → deactivate()
 *   GET  admin-users/{id}/reset-password   → resetPassword()
 *   GET  admin-users/{id}/send-credentials → sendCredentials()
 */
class AdminUserController extends AdminController
{
    use IpScopeable;

    protected $title = 'Admin User Management';

    // ─── ROLE LABELS ──────────────────────────────────────────────────────────

    private const ROLE_COLOURS = [
        'super_admin'      => 'danger',
        'ip_manager'       => 'primary',
        'me_officer'       => 'info',
        'content_manager'  => 'warning',
        'field_facilitator'=> 'success',
        'vsla_treasurer'   => 'default',
        'farmer_member'    => 'default',
    ];

    // ─── GATE CHECK ───────────────────────────────────────────────────────────

    /**
     * Only super admins and IP managers may access this section.
     * All other roles are redirected with an error toast.
     */
    private function gate(): ?bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        $admin = Admin::user();
        if ($admin && $this->userHasRoleSlug($admin, 'ip_manager')) {
            return true;
        }
        admin_toastr('Access denied: Admin User Management requires IP Manager or Super Admin role.', 'error');
        return false;
    }

    // ─── INDEX ────────────────────────────────────────────────────────────────

    public function index(Content $content)
    {
        if (!$this->gate()) {
            return redirect(admin_url('/'));
        }

        return $content
            ->title('Admin User Management')
            ->description('Manage all admin portal accounts — create, edit, activate, deactivate, and reset credentials')
            ->row(function (Row $row) {
                $this->renderSummaryPanel($row);
            })
            ->row(function (Row $row) {
                $row->column(12, $this->grid());
            });
    }

    // ─── SUMMARY PANEL ────────────────────────────────────────────────────────

    private function renderSummaryPanel(Row $row): void
    {
        $ipId          = $this->getAdminIpId();
        $isSuperAdmin  = $this->isSuperAdmin();

        // ── Role counts ───────────────────────────────────────────────────
        $roleCounts = DB::table('admin_role_users as aru')
            ->join('admin_roles as ar',  'aru.role_id',  '=', 'ar.id')
            ->join('users as u',         'aru.user_id',  '=', 'u.id')
            ->where('ar.slug', '!=', 'farmer_member')
            ->when($ipId !== null, fn($q) => $q->where('u.ip_id', $ipId))
            ->select('ar.name', 'ar.slug', DB::raw('COUNT(DISTINCT aru.user_id) as total'))
            ->groupBy('ar.id', 'ar.name', 'ar.slug')
            ->orderByRaw("FIELD(ar.slug, 'super_admin','ip_manager','me_officer','content_manager','field_facilitator','vsla_treasurer','farmer_member')")
            ->get();

        // ── Active / inactive counts across portal-level users (excludes farmer_member) ──
        $adminUserIds = DB::table('admin_role_users as aru')
            ->join('admin_roles as ar', 'aru.role_id', '=', 'ar.id')
            ->where('ar.slug', '!=', 'farmer_member')
            ->distinct()
            ->pluck('aru.user_id');

        $statusRow = DB::table('users')
            ->whereIn('id', $adminUserIds)
            ->when($ipId !== null, fn($q) => $q->where('ip_id', $ipId))
            ->selectRaw("
                COUNT(*)                                                               AS total,
                SUM(CASE WHEN status IN ('1','Active')   THEN 1 ELSE 0 END)           AS active,
                SUM(CASE WHEN status IN ('0','Inactive') THEN 1 ELSE 0 END)           AS inactive,
                SUM(CASE WHEN status NOT IN ('1','Active','0','Inactive') THEN 1 ELSE 0 END) AS other
            ")
            ->first();

        // ── IP breakdown (super admin only) ───────────────────────────────
        $ipBreakdown = collect();
        if ($isSuperAdmin) {
            $ipBreakdown = DB::table('admin_role_users as aru')
                ->join('users as u',                   'aru.user_id', '=', 'u.id')
                ->leftJoin('implementing_partners as ip', 'u.ip_id',   '=', 'ip.id')
                ->selectRaw('COALESCE(ip.name, "— No IP") as ip_name, COUNT(DISTINCT aru.user_id) as cnt')
                ->groupBy('u.ip_id', 'ip.name')
                ->orderByDesc('cnt')
                ->get();
        }

        $row->column(12, function (Column $col) use ($roleCounts, $statusRow, $ipBreakdown, $isSuperAdmin) {

            // ── Status summary cards ──────────────────────────────────────
            $total    = (int) ($statusRow->total    ?? 0);
            $active   = (int) ($statusRow->active   ?? 0);
            $inactive = (int) ($statusRow->inactive ?? 0);

            $html = "<div style='margin-bottom:16px;'>";
            $html .= "<div style='display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;'>";

            foreach ([
                ['Total Admin Users',  $total,    '#607d8b', 'users'],
                ['Active',             $active,   '#4caf50', 'check-circle'],
                ['Inactive / Locked',  $inactive, '#f44336', 'times-circle'],
            ] as [$label, $num, $colour, $icon]) {
                $html .= "<div style='flex:1;min-width:140px;background:#fff;border:1px solid #ddd;"
                       . "border-top:3px solid {$colour};padding:14px 16px;border-radius:2px;text-align:center;'>"
                       . "<div style='font-size:24px;font-weight:700;color:{$colour};'>{$num}</div>"
                       . "<div style='color:#666;font-size:12px;margin-top:4px;'>"
                       . "<i class='fa fa-{$icon}'></i>&nbsp;{$label}</div>"
                       . "</div>";
            }

            $html .= "</div>"; // cards row

            // ── Role distribution table ───────────────────────────────────
            $html .= "<div style='display:flex;gap:12px;flex-wrap:wrap;'>";

            $html .= "<div style='flex:2;min-width:280px;background:#fff;border:1px solid #ddd;border-radius:2px;padding:14px;'>";
            $html .= "<h5 style='margin:0 0 10px;color:#555;'><i class='fa fa-shield'></i>&nbsp; Users by Role</h5>";
            $html .= "<table class='table table-condensed' style='margin:0;font-size:13px;'><tbody>";

            foreach ($roleCounts as $rc) {
                $colour = self::ROLE_COLOURS[$rc->slug] ?? 'default';
                $html .= "<tr>"
                       . "<td style='border:0;padding:4px 0;'>"
                       . "<span class='label label-{$colour}'>{$rc->name}</span>"
                       . "</td>"
                       . "<td style='border:0;padding:4px 0;text-align:right;font-weight:600;'>{$rc->total}</td>"
                       . "</tr>";
            }

            if ($roleCounts->isEmpty()) {
                $html .= "<tr><td colspan='2' class='text-muted' style='border:0;'>No admin users found.</td></tr>";
            }

            $html .= "</tbody></table></div>";

            // ── IP breakdown (super admin only) ───────────────────────────
            if ($isSuperAdmin && $ipBreakdown->isNotEmpty()) {
                $html .= "<div style='flex:2;min-width:280px;background:#fff;border:1px solid #ddd;border-radius:2px;padding:14px;'>";
                $html .= "<h5 style='margin:0 0 10px;color:#555;'><i class='fa fa-sitemap'></i>&nbsp; Users by Implementing Partner</h5>";
                $html .= "<table class='table table-condensed' style='margin:0;font-size:13px;'><tbody>";

                foreach ($ipBreakdown as $ib) {
                    $bar = min(100, (int) ($total > 0 ? ($ib->cnt / $total) * 100 : 0));
                    $html .= "<tr>"
                           . "<td style='border:0;padding:4px 0;'>" . e($ib->ip_name) . "</td>"
                           . "<td style='border:0;padding:4px 0;width:120px;'>"
                           . "<div style='background:#eee;border-radius:3px;height:10px;'>"
                           . "<div style='background:#2196f3;width:{$bar}%;height:10px;border-radius:3px;'></div>"
                           . "</div></td>"
                           . "<td style='border:0;padding:4px 0;text-align:right;font-weight:600;'>{$ib->cnt}</td>"
                           . "</tr>";
                }

                $html .= "</tbody></table></div>";
            }

            $html .= "</div>"; // flex row
            $html .= "</div>"; // wrapper

            $col->append($html);
        });
    }

    // ─── GRID ─────────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new User());

        $isSuperAdmin = $this->isSuperAdmin();
        $ipId         = $this->getAdminIpId();

        // Scope: only users with a portal-level role (excludes farmer_member — regular app-only users)
        $adminUserIds = DB::table('admin_role_users as aru')
            ->join('admin_roles as ar', 'aru.role_id', '=', 'ar.id')
            ->where('ar.slug', '!=', 'farmer_member')
            ->distinct()
            ->pluck('aru.user_id');

        $grid->model()
            ->whereIn('id', $adminUserIds)
            ->when($ipId !== null, fn($q) => $q->where('ip_id', $ipId))
            ->orderBy('id', 'desc');

        $grid->disableBatchActions();

        // ── Quick search ──────────────────────────────────────────────────
        $grid->quickSearch('name', 'first_name', 'last_name', 'phone_number', 'email')
            ->placeholder('Search name, phone or email…');

        // ── Filters ───────────────────────────────────────────────────────
        $grid->filter(function ($filter) use ($isSuperAdmin) {
            $filter->disableIdFilter();

            if ($isSuperAdmin) {
                $filter->equal('ip_id', 'Implementing Partner')
                    ->select(ImplementingPartner::orderBy('name')->pluck('name', 'id')->toArray());
            }

            $filter->like('name',         'Name');
            $filter->like('phone_number', 'Phone');
            $filter->like('email',        'Email');

            $roleOptions = DB::table('admin_roles')
                ->whereNotIn('slug', ['farmer_member'])
                ->pluck('name', 'id')
                ->toArray();

            $filter->where(function ($query) {
                $roleId = $this->input;
                if ($roleId) {
                    $query->whereHas('roles', fn($q) => $q->where('admin_roles.id', $roleId));
                }
            }, 'Role', 'role_filter')->select($roleOptions);

            $filter->equal('status', 'Status')->select([
                '1'        => 'Active',
                'Active'   => 'Active (string)',
                '0'        => 'Inactive',
                'Inactive' => 'Inactive (string)',
            ]);
        });

        // ── Columns ───────────────────────────────────────────────────────
        $grid->column('id', 'ID')->sortable()->width(60);

        $grid->column('name', 'Name')->display(function () {
            $name = e($this->name ?: trim($this->first_name . ' ' . $this->last_name));
            $code = $this->member_code ? "<br><small class='text-muted'>{$this->member_code}</small>" : '';
            return "<strong>{$name}</strong>{$code}";
        })->sortable();

        $grid->column('phone_number', 'Phone')->display(function ($v) {
            return $v
                ? "<a href='tel:{$v}'><i class='fa fa-phone text-success'></i> " . e($v) . "</a>"
                : '<span class="text-muted">—</span>';
        });

        $grid->column('email', 'Email')->display(function ($v) {
            return $v ? e($v) : '<span class="text-muted">—</span>';
        });

        $grid->column('roles_list', 'Roles')->display(function () {
            try {
                $roles = $this->roles()->get(['name', 'slug']);
                if ($roles->isEmpty()) {
                    return '<span class="label label-default">—</span>';
                }
                return $roles->map(function ($r) {
                    $colour = self::ROLE_COLOURS[$r->slug] ?? 'default';
                    return "<span class='label label-{$colour}'>" . e($r->name) . "</span>";
                })->implode(' ');
            } catch (\Throwable $e) {
                return '<span class="text-muted">—</span>';
            }
        });

        if ($isSuperAdmin) {
            $grid->column('ip_id', 'Partner')->display(function ($id) {
                if (!$id) return '<span class="text-muted">—</span>';
                $ip = ImplementingPartner::find($id);
                return $ip ? e($ip->short_name ?: $ip->name) : "IP #{$id}";
            });
        }

        $grid->column('status', 'Status')->display(function ($v) {
            $active = in_array($v, ['1', 'Active'], true);
            return $active
                ? "<span class='label label-success'><i class='fa fa-check'></i> Active</span>"
                : "<span class='label label-danger'><i class='fa fa-ban'></i> Inactive</span>";
        })->sortable();

        $grid->column('last_seen', 'Last Seen')->display(function ($v) {
            return $v ?: '<span class="text-muted">Never</span>';
        })->sortable();

        $grid->column('created_at', 'Created')->date('d M Y')->sortable();

        // ── Row actions ───────────────────────────────────────────────────
        $grid->actions(function ($actions) use ($isSuperAdmin) {
            $id     = $actions->getKey();
            $status = $actions->row->status;
            $isActive = in_array($status, ['1', 'Active'], true);

            // Protected admin account (ID 1) — cannot be deleted
            if ((int) $id === 1) {
                $actions->disableDelete();
            }

            // Activate / Deactivate toggle
            if ($isActive) {
                $deactivateUrl = admin_url("admin-users/{$id}/deactivate");
                $actions->prepend(
                    "<a href='{$deactivateUrl}' class='btn btn-xs btn-warning' "
                    . "onclick=\"return confirm('Deactivate this account?')\" title='Deactivate'>"
                    . "<i class='fa fa-ban'></i> Deactivate</a>&nbsp;"
                );
            } else {
                $activateUrl = admin_url("admin-users/{$id}/activate");
                $actions->prepend(
                    "<a href='{$activateUrl}' class='btn btn-xs btn-success' title='Activate'>"
                    . "<i class='fa fa-check'></i> Activate</a>&nbsp;"
                );
            }

            // Change password
            $changePwdUrl = admin_url("admin-users/{$id}/change-password");
            $actions->prepend(
                "<a href='{$changePwdUrl}' class='btn btn-xs btn-primary' title='Change Password'>"
                . "<i class='fa fa-key'></i></a>&nbsp;"
            );

            // Reset password
            $resetUrl = admin_url("admin-users/{$id}/reset-password");
            $actions->prepend(
                "<a href='{$resetUrl}' class='btn btn-xs btn-default' "
                . "onclick=\"return confirm('Reset password to phone number digits?')\" title='Reset Password'>"
                . "<i class='fa fa-key'></i></a>&nbsp;"
            );

            // Send credentials
            $credUrl = admin_url("admin-users/{$id}/send-credentials");
            $actions->prepend(
                "<a href='{$credUrl}' class='btn btn-xs btn-info' "
                . "onclick=\"return confirm('Send login credentials via SMS?')\" title='Send Credentials'>"
                . "<i class='fa fa-envelope'></i></a>&nbsp;"
            );

            // Delete only for super admins
            if (!$isSuperAdmin) {
                $actions->disableDelete();
            }
        });

        return $grid;
    }

    // ─── DETAIL / SHOW ────────────────────────────────────────────────────────

    protected function detail($id)
    {
        $user = User::findOrFail($id);

        if (!$this->isSuperAdmin()) {
            $ipId = $this->getAdminIpId();
            if ($ipId !== null && (int) $user->ip_id !== $ipId) {
                admin_toastr('Access denied.', 'error');
                return redirect(admin_url('admin-users'));
            }
        }

        $show = new Show($user);
        $show->panel()->style('primary')->title('Admin User Profile');

        $show->field('id', 'ID');

        $show->divider('Personal Information');
        $show->field('first_name',     'First Name');
        $show->field('last_name',      'Last Name');
        $show->field('name',           'Full Name');
        $show->field('sex',            'Gender');
        $show->field('dob',            'Date of Birth');
        $show->field('national_id_number', 'National ID (NIN)');

        $show->divider('Contact');
        $show->field('phone_number', 'Phone Number')->as(function ($v) {
            return $v ? '<a href="tel:' . $v . '">' . e($v) . '</a>' : '—';
        })->unescape();
        $show->field('email', 'Email')->as(function ($v) {
            return $v ? '<a href="mailto:' . $v . '">' . e($v) . '</a>' : '—';
        })->unescape();

        $show->divider('Work');
        $show->field('position',               'Position');
        $show->field('department',             'Department');
        $show->field('contract_type',          'Contract Type');
        $show->field('facilitator_start_date', 'Start Date');

        $show->divider('System Access');
        $show->field('username',   'Username');
        $show->field('user_type',  'User Type');
        $show->field('ip_id',      'Implementing Partner')->as(function ($id) {
            $ip = ImplementingPartner::find($id);
            return $ip ? e("{$ip->name} ({$ip->short_name})") : '—';
        });
        $show->field('status', 'Status')->as(function ($v) {
            return in_array($v, ['1', 'Active'], true) ? '✔ Active' : '✘ Inactive';
        });
        $show->field('last_seen', 'Last Seen')->as(fn($v) => $v ?: 'Never');

        $show->divider('Roles Assigned');
        $show->field('id', 'Roles')->as(function ($id) {
            try {
                $roles = User::find($id)->roles()->get(['name', 'slug']);
                if ($roles->isEmpty()) return '—';
                return $roles->map(fn($r) => e($r->name))->implode(', ');
            } catch (\Throwable $e) {
                return '—';
            }
        });

        $show->divider();
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    // ─── FORM ─────────────────────────────────────────────────────────────────

    protected function form()
    {
        $form         = new Form(new User());
        $isSuperAdmin = $this->isSuperAdmin();
        $adminUser    = Admin::user();
        $adminIpId    = $isSuperAdmin ? null : ($adminUser ? (int) $adminUser->ip_id : null);

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
            $row->width(4)->date('dob', 'Date of Birth');
            $row->width(4)->text('national_id_number', 'National ID (NIN)');
            $row->width(4)->select('marital_status', 'Marital Status')
                ->options(['Single' => 'Single', 'Married' => 'Married', 'Widowed' => 'Widowed', 'Divorced' => 'Divorced']);
        });

        // ── Contact ───────────────────────────────────────────────────────
        $form->divider('Contact');

        $form->row(function ($row) {
            $row->width(6)->text('phone_number', 'Phone Number')
                ->placeholder('e.g. 0771234567')
                ->creationRules(['nullable', 'unique:users,phone_number'])
                ->updateRules(['nullable', 'unique:users,phone_number,{{id}}']);
            $row->width(6)->email('email', 'Email Address')
                ->placeholder('e.g. user@example.com')
                ->creationRules(['nullable', 'email', 'unique:users,email'])
                ->updateRules(['nullable', 'email', 'unique:users,email,{{id}}']);
        });

        // ── Work Information ──────────────────────────────────────────────
        $form->divider('Work Information');

        $form->row(function ($row) {
            $row->width(4)->text('position',   'Position')->placeholder('e.g. IP Manager');
            $row->width(4)->text('department', 'Department')->placeholder('e.g. Field Operations');
            $row->width(4)->select('contract_type', 'Contract Type')
                ->options(['Full Time' => 'Full Time', 'Part Time' => 'Part Time']);
        });

        $form->row(function ($row) {
            $row->width(4)->date('facilitator_start_date', 'Start Date')
                ->help('Date this user started in their role');
            $row->width(4)->select('district_name', 'District / Location')
                ->options($this->northernUgandaDistricts());
            $row->width(4)->text('village', 'Village / Town');
        });

        // ── System Access & Roles ─────────────────────────────────────────
        $form->divider('System Access & Roles');

        // Implementing Partner + Roles + Status — all in one row so the IP field renders reliably
        $ipSelectArgs = [];
        if ($isSuperAdmin) {
            $allIpOptions = ImplementingPartner::orderBy('name')->get()
                ->mapWithKeys(function ($ip) {
                    $short = $ip->short_name ? " ({$ip->short_name})" : '';
                    return [$ip->id => "{$ip->name}{$short}"];
                })->toArray();
            $ipSelectArgs = ['options' => $allIpOptions, 'default' => null, 'readOnly' => false];
        } else {
            $ipOptions = [];
            if ($adminIpId) {
                $ip = ImplementingPartner::find($adminIpId);
                if ($ip) {
                    $ipOptions[$ip->id] = $ip->name . ($ip->short_name ? " ({$ip->short_name})" : '');
                }
            }
            $ipSelectArgs = ['options' => $ipOptions, 'default' => $adminIpId, 'readOnly' => true];
        }

        $form->row(function ($row) use ($isSuperAdmin, $ipSelectArgs) {
            $roleModel  = config('admin.database.roles_model', \Encore\Admin\Auth\Database\Role::class);
            $rolesQuery = null;
            if ($roleModel && class_exists($roleModel)) {
                $rolesQuery = $roleModel::query()->whereNotIn('slug', ['farmer_member']);
                if (!$isSuperAdmin) {
                    $rolesQuery->where('slug', '!=', 'super_admin');
                }
            }

            $ipField = $row->width(4)->select('ip_id', 'Implementing Partner')
                ->options($ipSelectArgs['options']);
            if ($ipSelectArgs['default'] !== null) {
                $ipField->default($ipSelectArgs['default']);
            }
            if ($ipSelectArgs['readOnly']) {
                $ipField->readOnly();
            }

            $row->width(5)->multipleSelect('roles', 'Assigned Roles')
                ->options($rolesQuery ? $rolesQuery->pluck('name', 'id')->toArray() : [])
                ->rules('required');

            $row->width(3)->select('status', 'Account Status')
                ->options(['1' => 'Active', '0' => 'Inactive'])
                ->default('1')
                ->required();
        });

        // ── Account & Security ────────────────────────────────────────────
        $form->divider('Account & Security');

        $form->text('username', 'Username')
            ->placeholder('Auto-filled from phone number if blank');

        if (request()->is('*/create')) {
            // New record: set initial password here
            $form->password('password', 'Password');
        } else {
            // Existing record: password is managed on its own dedicated page
            $editId = request()->route('admin_user') ?? basename(request()->path(), '/edit');
            $pwdUrl = admin_url("admin-users/{$editId}/change-password");
            $form->html('
<div class="form-group">
    <label class="col-md-2 control-label">Password</label>
    <div class="col-md-8" style="padding-top:7px;">
        <a href="' . $pwdUrl . '" class="btn btn-sm btn-warning">
            <i class="fa fa-key"></i>&nbsp; Change Password
        </a>
        <span style="margin-left:10px;color:#888;font-size:12px;">Opens the dedicated password change page.</span>
    </div>
</div>
            ');
        }

        // ── Saving logic ──────────────────────────────────────────────────
        $form->saving(function (Form $form) use ($adminIpId, $isSuperAdmin) {

            // Build full name
            $form->name = trim(($form->first_name ?? '') . ' ' . ($form->last_name ?? ''));

            // All users in this controller are admin portal accounts
            $form->user_type = 'Admin';

            // Strip null/empty entries from roles multiselect
            if (is_array($form->roles)) {
                $form->roles = array_values(
                    array_filter($form->roles, fn($r) => $r !== null && $r !== '')
                );
            }

            // Non-super-admins: always lock ip_id to their own partner
            if (!$isSuperAdmin && $adminIpId !== null) {
                $form->ip_id = $adminIpId;
            }

            if ($form->isCreating()) {

                // Username: default to phone digits
                if (empty(trim((string) $form->username)) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                } elseif (empty(trim((string) $form->username))) {
                    $form->username = 'admin_' . uniqid();
                }

                // Password: default to phone digits
                $plain          = !empty($form->phone_number)
                    ? preg_replace('/[^0-9]/', '', $form->phone_number)
                    : '123456';
                $form->password = empty(trim((string) $form->password))
                    ? Hash::make($plain)
                    : Hash::make($form->password);

                $form->created_by_id           = Admin::user()->id ?? null;
                $form->registered_by_id        = Admin::user()->id ?? null;
                $form->onboarding_step         = 'step_7_complete';
                $form->onboarding_completed_at = now();

                if (empty($form->ip_id)) {
                    $form->ip_id = Admin::user()->ip_id ?? null;
                }

            } else {

                // ── PASSWORD GUARD (edit mode) ────────────────────────────
                // Remove 'password' from $form->inputs immediately so it can NEVER
                // reach fill()+save(), no matter what happens below.
                // Password changes must go through: admin-users/{id}/change-password
                unset($form->password);

                // Edit: load original record so we can fall back to existing values
                $original = $form->model();

                // ── phone_number ─────────────────────────────────────────
                if (empty(trim((string) $form->phone_number))) {
                    if (!empty($original->phone_number)) {
                        $form->phone_number = $original->phone_number;
                    }
                    // else: they're clearing it intentionally — allow it
                }

                // ── email ─────────────────────────────────────────────────
                if (empty(trim((string) $form->email))) {
                    if (!empty($original->email)) {
                        $form->email = $original->email;
                    }
                }

                // ── username ──────────────────────────────────────────────
                if (empty(trim((string) $form->username))) {
                    if (!empty($original->username)) {
                        $form->username = $original->username;  // keep existing
                    } elseif (!empty($form->phone_number)) {
                        $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                    }
                    // if still empty after the above, leave unset (DB keeps its value)
                }
            }
        });

        $form->disableViewCheck();
        $form->disableCreatingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }

    // ─── CHANGE PASSWORD PAGE ─────────────────────────────────────────────────

    /**
     * Show a dedicated password-change form for a single admin user.
     */
    public function changePassword(int $id, Content $content)
    {
        $user = User::findOrFail($id);

        if (!$this->isSuperAdmin()) {
            $ipId = $this->getAdminIpId();
            if ($ipId !== null && (int) $user->ip_id !== $ipId) {
                admin_toastr('Access denied.', 'error');
                return redirect(admin_url('admin-users'));
            }
            if ($this->userHasRoleSlug($user, 'super_admin')) {
                admin_toastr('Access denied: you cannot change a Super Admin password.', 'error');
                return redirect(admin_url('admin-users'));
            }
        }

        $postUrl   = admin_url("admin-users/{$id}/change-password");
        $cancelUrl = admin_url("admin-users/{$id}/edit");
        $name      = e($user->name ?: trim($user->first_name . ' ' . $user->last_name));
        $username  = e($user->username ?: '—');
        $phone     = e($user->phone_number ?: '—');
        $token     = csrf_token();

        $html = <<<HTML
<div class="box box-primary" style="max-width:580px;">
  <div class="box-header with-border">
    <h3 class="box-title"><i class="fa fa-key"></i>&nbsp; Change Password</h3>
  </div>
  <div class="box-body">
    <table class="table table-condensed" style="margin-bottom:20px;max-width:400px;">
      <tr><td style="color:#888;width:120px;">Name</td><td><strong>{$name}</strong></td></tr>
      <tr><td style="color:#888;">Username</td><td><strong>{$username}</strong></td></tr>
      <tr><td style="color:#888;">Phone</td><td><strong>{$phone}</strong></td></tr>
    </table>
    <form method="POST" action="{$postUrl}" id="chg-pwd-form">
      <input type="hidden" name="_token" value="{$token}">
      <div class="form-group">
        <label>New Password <span class="text-danger">*</span></label>
        <input type="password" name="new_password" id="npwd"
               class="form-control" style="max-width:360px;"
               placeholder="Enter new password" required>
      </div>
      <div class="form-group">
        <label>Confirm Password <span class="text-danger">*</span></label>
        <input type="password" name="confirm_password" id="cpwd"
               class="form-control" style="max-width:360px;"
               placeholder="Re-enter new password" required>
        <small id="pwd-msg" style="display:none;margin-top:4px;"></small>
      </div>
      <div style="margin-top:24px;">
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-save"></i>&nbsp;Update Password
        </button>
        &nbsp;
        <a href="{$cancelUrl}" class="btn btn-default">
          <i class="fa fa-arrow-left"></i>&nbsp;Cancel
        </a>
      </div>
    </form>
  </div>
</div>
<script>
$(function () {
  function checkMatch() {
    var p1 = $('#npwd').val(), p2 = $('#cpwd').val(), \$m = $('#pwd-msg');
    if (!p2) { \$m.hide(); return; }
    if (p1 === p2) { \$m.text('Passwords match ✓').css('color','#3c763d').show(); }
    else           { \$m.text('Passwords do not match').css('color','#a94442').show(); }
  }
  $('#npwd, #cpwd').on('input', checkMatch);

  $('#chg-pwd-form').on('submit', function (e) {
    var p1 = $('#npwd').val(), p2 = $('#cpwd').val();
    if (p1.length < 4) { e.preventDefault(); alert('Password must be at least 4 characters.'); return; }
    if (p1 !== p2)     { e.preventDefault(); alert('Passwords do not match.'); }
  });
});
</script>
HTML;

        return $content
            ->title('Change Password')
            ->description('Set a new password for ' . $name)
            ->body($html);
    }

    /**
     * Process the password change form submission.
     */
    public function updatePassword(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if (!$this->isSuperAdmin()) {
            $ipId = $this->getAdminIpId();
            if ($ipId !== null && (int) $user->ip_id !== $ipId) {
                admin_toastr('Access denied.', 'error');
                return redirect(admin_url('admin-users'));
            }
            if ($this->userHasRoleSlug($user, 'super_admin')) {
                admin_toastr('Access denied: cannot change a Super Admin password.', 'error');
                return redirect(admin_url('admin-users'));
            }
        }

        $newPwd     = trim($request->input('new_password', ''));
        $confirmPwd = trim($request->input('confirm_password', ''));

        if (empty($newPwd)) {
            admin_toastr('Password cannot be empty.', 'error');
            return redirect()->back();
        }

        if ($newPwd !== $confirmPwd) {
            admin_toastr('Passwords do not match. No changes were saved.', 'error');
            return redirect()->back();
        }

        if (strlen($newPwd) < 4) {
            admin_toastr('Password must be at least 4 characters.', 'error');
            return redirect()->back();
        }

        try {
            DB::table('users')->where('id', $id)->update(['password' => Hash::make($newPwd)]);
            admin_toastr("Password updated successfully for {$user->name}.", 'success');
            Log::info("AdminUserController: password changed for user #{$id} by admin #" . (Admin::user()->id ?? '?'));
        } catch (\Throwable $e) {
            admin_toastr('Failed to update password: ' . $e->getMessage(), 'error');
            Log::error("AdminUserController: password update failed for #{$id}: " . $e->getMessage());
            return redirect()->back();
        }

        return redirect(admin_url("admin-users/{$id}/edit"));
    }

    // ─── CUSTOM ACTIONS ───────────────────────────────────────────────────────

    /**
     * Set the user's status to Active.
     */
    public function activate(int $id)
    {
        return $this->setStatus($id, '1', 'activated');
    }

    /**
     * Set the user's status to Inactive.
     */
    public function deactivate(int $id)
    {
        return $this->setStatus($id, '0', 'deactivated');
    }

    private function setStatus(int $id, string $status, string $verb)
    {
        $user = User::findOrFail($id);

        if (!$this->isSuperAdmin()) {
            $ipId = $this->getAdminIpId();
            if ($ipId !== null && (int) $user->ip_id !== $ipId) {
                admin_toastr('Access denied: this account belongs to a different Implementing Partner.', 'error');
                return redirect(admin_url('admin-users'));
            }
            // IP Managers cannot deactivate super admins
            if ($this->userHasRoleSlug($user, 'super_admin')) {
                admin_toastr('Access denied: you cannot modify a Super Admin account.', 'error');
                return redirect(admin_url('admin-users'));
            }
        }

        try {
            DB::table('users')->where('id', $id)->update(['status' => $status]);
            admin_toastr("Account for '{$user->name}' has been {$verb} successfully.", 'success');
            Log::info("AdminUserController: user #{$id} {$verb} by admin #" . (Admin::user()->id ?? '?'));
        } catch (\Throwable $e) {
            admin_toastr('Failed to update account status: ' . $e->getMessage(), 'error');
            Log::error("AdminUserController: status update failed for #{$id}: " . $e->getMessage());
        }

        return redirect(admin_url('admin-users'));
    }

    /**
     * Reset the user's password to their phone number digits (or '123456' fallback).
     */
    public function resetPassword(int $id)
    {
        $user = User::findOrFail($id);

        if (!$this->isSuperAdmin()) {
            $ipId = $this->getAdminIpId();
            if ($ipId !== null && (int) $user->ip_id !== $ipId) {
                admin_toastr('Access denied.', 'error');
                return redirect(admin_url('admin-users'));
            }
            if ($this->userHasRoleSlug($user, 'super_admin')) {
                admin_toastr('Access denied: you cannot reset a Super Admin password.', 'error');
                return redirect(admin_url('admin-users'));
            }
        }

        $plain = !empty($user->phone_number)
            ? preg_replace('/[^0-9]/', '', $user->phone_number)
            : '123456';

        try {
            DB::table('users')->where('id', $id)->update(['password' => Hash::make($plain)]);
            admin_toastr("Password reset successfully. New password: {$plain}", 'success');
            Log::info("AdminUserController: password reset for #{$id} by admin #" . (Admin::user()->id ?? '?'));
        } catch (\Throwable $e) {
            admin_toastr('Password reset failed: ' . $e->getMessage(), 'error');
            Log::error("AdminUserController: password reset failed for #{$id}: " . $e->getMessage());
        }

        return redirect(admin_url('admin-users'));
    }

    /**
     * Send login credentials (username + password hint) via SMS.
     */
    public function sendCredentials(int $id)
    {
        $user = User::findOrFail($id);

        if (!$this->isSuperAdmin()) {
            $ipId = $this->getAdminIpId();
            if ($ipId !== null && (int) $user->ip_id !== $ipId) {
                admin_toastr('Access denied.', 'error');
                return redirect(admin_url('admin-users'));
            }
        }

        $email = $user->email ?: $user->username;
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            admin_toastr('This user has no valid email address — cannot send credentials.', 'error');
            return redirect(admin_url('admin-users'));
        }

        $password = preg_replace('/[^0-9]/', '', $user->phone_number ?: '') ?: '123456';

        try {
            \App\Models\Utils::send_credentials_email($user, $password, 'Admin');
            admin_toastr("Credentials emailed to {$user->name} ({$email})", 'success');
            Log::info("AdminUserController: credentials email sent to #{$id}");
        } catch (\Exception $e) {
            admin_toastr('Failed to send email: ' . $e->getMessage(), 'error');
            Log::error("AdminUserController: credentials email failed for #{$id}: " . $e->getMessage());
        }

        return redirect(admin_url('admin-users'));
    }
}
