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

class FacilitatorController extends AdminController
{
    use IpScopeable;

    protected $title = 'Facilitators';

    /**
     * Subquery that returns all user IDs who are linked to at least one
     * group as a facilitator OR who have a facilitator_start_date set.
     */
    private function facilitatorIds()
    {
        $fromGroups = DB::table('ffs_groups')
            ->whereNotNull('facilitator_id')
            ->distinct()
            ->pluck('facilitator_id');

        $fromDate = DB::table('users')
            ->whereNotNull('facilitator_start_date')
            ->pluck('id');

        return $fromGroups->merge($fromDate)->unique()->values();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GRID
    // ─────────────────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new User());

        $currentAdmin  = Admin::user();
        $isSuperAdmin  = $this->isSuperAdmin();
        $isFacilitator = !$isSuperAdmin && $currentAdmin
            && $this->userHasRoleSlug($currentAdmin, 'facilitator');
        $ipId = $this->getAdminIpId();

        // ── Base scope: users who are facilitators (linked to a group OR have start date) ──
        $ids = $this->facilitatorIds();
        $grid->model()
            ->whereIn('id', $ids)
            ->selectRaw('users.*, (SELECT COUNT(*) FROM ffs_groups WHERE ffs_groups.facilitator_id = users.id) AS groups_count_sql, (SELECT COUNT(*) FROM users AS m WHERE m.group_id IN (SELECT id FROM ffs_groups WHERE ffs_groups.facilitator_id = users.id)) AS members_profiled_sql')
            ->orderBy('id', 'desc');

        // ── Three-tier access ──────────────────────────────────────────────
        if ($isFacilitator) {
            // Facilitators see only themselves
            $grid->model()->where('id', $currentAdmin->id);
        } elseif (!$isSuperAdmin && $ipId) {
            // IP managers see only their IP's facilitators
            $grid->model()->where('ip_id', $ipId);
        }
        // Super admins see all

        // ── Search ────────────────────────────────────────────────────────
        $grid->quickSearch('name', 'first_name', 'last_name', 'phone_number', 'email')
            ->placeholder('Search name, phone or email…');

        // ── Facilitators cannot create/delete records for others ──────────
        if ($isFacilitator) {
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });
        }

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

            $filter->equal('district_name', 'District')
                ->select($this->northernUgandaDistricts());

            $filter->equal('status', 'Status')->select(['1' => 'Active', '0' => 'Inactive']);
        });

        // ── Columns ───────────────────────────────────────────────────────
        $grid->column('id', 'ID')->sortable();

        $grid->column('name', 'Facilitator')->display(function () {
            $name = e($this->name ?: trim("{$this->first_name} {$this->last_name}"));
            return "<strong>{$name}</strong>";
        })->sortable();

        $grid->column('phone_number', 'Phone');
        $grid->column('email',        'Email')->hide();
        $grid->column('sex',          'Gender');

        $grid->column('district_name', 'District')->display(function ($v) {
            return $v ?: '—';
        })->hide();

        if ($isSuperAdmin) {
            $grid->column('ip_id', 'Partner')->display(function ($id) {
                if (!$id) return '—';
                $ip = ImplementingPartner::find($id);
                return $ip ? e($ip->short_name ?: $ip->name) : "IP #{$id}";
            });
        }

        $grid->column('facilitator_start_date', 'Start Date')->date('d M Y')->sortable();

        $grid->column('groups_count_sql', 'Groups')->display(function ($count) {
            $count = (int)$count;
            if (!$count) return '<span class="label label-default">0</span>';
            return '<span class="label label-primary">' . $count . '</span>';
        })->sortable();

        $grid->column('members_profiled_sql', 'Members Profiled')->display(function ($count) {
            $count = (int)$count;
            if (!$count) return '<span class="label label-default">0</span>';
            return '<span class="label label-success">' . $count . '</span>';
        })->sortable();

        return $grid;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Inject a visible section heading (same approach as FfsGroupController).
     */
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
        $facilitator = User::findOrFail($id);

        if (!$this->verifyIpAccess($facilitator)) {
            return $this->denyIpAccess();
        }

        $currentAdmin = Admin::user();
        if (
            !$this->isSuperAdmin()
            && $this->userHasRoleSlug($currentAdmin, 'facilitator')
            && (int) $facilitator->id !== (int) $currentAdmin->id
        ) {
            return $this->denyIpAccess();
        }

        // Pre-load groups and compute stats
        $groups      = \App\Models\FfsGroup::where('facilitator_id', $facilitator->id)
                            ->withCount('members')
                            ->with('district')
                            ->orderBy('status')->orderBy('name')
                            ->get();
        $groupCount  = $groups->count();
        $memberCount = $groups->sum('members_count');

        $show = new Show($facilitator);
        $show->panel()->style('primary')
            ->title(e($facilitator->name ?: trim($facilitator->first_name . ' ' . $facilitator->last_name)));

        // ── Personal Information ──────────────────────────────────────────
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

        // ── Location ─────────────────────────────────────────────────────
        $this->showDetailSection($show, 'Location', 'map-marker');
        $show->field('district_name', 'District')->as(fn($v) => $v ? ucwords(strtolower($v)) : '-');
        $show->field('village', 'Village')->as(fn($v) => $v ? ucwords(strtolower($v)) : '-');

        // ── Work Assignment ───────────────────────────────────────────────
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
        $show->field('facilitator_start_date', 'Facilitator Since')->as(function ($v) {
            if (!$v) return '-';
            $date = \Carbon\Carbon::parse($v);
            $years  = $date->diffInYears(now());
            $months = $date->diffInMonths(now()) % 12;
            $parts  = [];
            if ($years  > 0) $parts[] = "{$years} yr";
            if ($months > 0) $parts[] = "{$months} mo";
            $dur = $parts ? " <span class='text-muted'>(" . implode(' ', $parts) . " experience)</span>" : '';
            return $date->format('d M Y') . $dur;
        })->unescape();
        $show->field('status', 'Account Status')->as(function ($v) {
            return $v == 1
                ? "<span class='label label-success'><i class='fa fa-check'></i> Active</span>"
                : "<span class='label label-default'>Inactive</span>";
        })->unescape();

        // ── Statistics ────────────────────────────────────────────────────
        $this->showDetailSection($show, 'Performance Summary', 'bar-chart');
        $show->field('_stat_groups', 'Groups Managed')->as(function () use ($groupCount) {
            return "<span style='font-size:22px;font-weight:700;color:#05179F;'>{$groupCount}</span>"
                . " <span class='text-muted'>group" . ($groupCount != 1 ? 's' : '') . "</span>";
        })->unescape();
        $show->field('_stat_members', 'Members Profiled')->as(function () use ($memberCount) {
            return "<span style='font-size:22px;font-weight:700;color:#00a65a;'>{$memberCount}</span>"
                . " <span class='text-muted'>member" . ($memberCount != 1 ? 's' : '') . " across all groups</span>";
        })->unescape();

        // ── Groups Managed ────────────────────────────────────────────────
        $this->showDetailSection($show, "Groups Managed ({$groupCount})", 'users');
        $show->field('_groups_table', 'Groups')->as(function () use ($groups) {
            if ($groups->isEmpty()) {
                return "<span class='text-muted'><i class='fa fa-info-circle'></i> No groups assigned yet</span>";
            }
            $rows = $groups->map(function ($g) {
                $url         = admin_url("ffs-all-groups/{$g->id}");
                $typeColors  = ['VSLA' => 'warning', 'FFS' => 'info', 'FBS' => 'primary', 'Association' => 'success'];
                $statColors  = ['Active' => 'success', 'Inactive' => 'default', 'Suspended' => 'warning', 'Graduated' => 'info'];
                $typeColor   = $typeColors[$g->type]   ?? 'default';
                $statColor   = $statColors[$g->status] ?? 'default';
                $members     = (int)($g->members_count ?? 0);
                $district    = $g->district_text
                    ? ucwords(strtolower($g->district_text))
                    : ($g->district ? $g->district->name : '-');
                return "<tr>"
                    . "<td><a href='{$url}' style='color:#337ab7;font-weight:500;'>"
                    .       "<i class='fa fa-external-link fa-fw'></i> " . e($g->name)
                    . "</a><br><small class='text-muted'>" . e($g->code ?: '') . "</small></td>"
                    . "<td><span class='label label-{$typeColor}'>" . e($g->type) . "</span></td>"
                    . "<td><span class='label label-{$statColor}'>" . e($g->status) . "</span></td>"
                    . "<td>" . e($district) . "</td>"
                    . "<td><strong>{$members}</strong></td>"
                    . "</tr>";
            })->implode('');

            return "<table class='table table-condensed table-bordered table-hover' style='margin:0;font-size:13px;'>"
                . "<thead style='background:#f4f4f4;'><tr>"
                . "<th>Group Name</th><th>Type</th><th>Status</th><th>District</th><th>Members</th>"
                . "</tr></thead>"
                . "<tbody>{$rows}</tbody>"
                . "</table>";
        })->unescape();

        // ── Audit ─────────────────────────────────────────────────────────
        $this->showDetailSection($show, 'Audit', 'clock-o');
        $show->field('created_at', 'Registered')->as(fn($d) => $d ? date('d M Y H:i', strtotime($d)) : '-');
        $show->field('updated_at', 'Last Updated')->as(fn($d) => $d ? date('d M Y H:i', strtotime($d)) : '-');

        return $show;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new User());

        $this->addIpFieldToForm($form);

        // Facilitators are Customer-type users (they use the mobile app)
        $form->hidden('user_type')->default('Customer');

        // ── Personal information ──────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(4)->text('first_name', 'First Name')->required();
            $row->width(4)->text('last_name',  'Last Name')->required();
            $row->width(4)->select('sex', 'Gender')
                ->options(['Male' => 'Male', 'Female' => 'Female'])
                ->default('Male')
                ->required();
        });

        $form->row(function ($row) {
            $row->width(6)->text('phone_number', 'Phone Number')
                ->placeholder('e.g. 0771234567 (optional)')
                ->creationRules(['nullable', 'unique:users,phone_number'])
                ->updateRules(['nullable', 'unique:users,phone_number,{{id}}'])
                ->help('Leave blank if facilitator has no phone — member code will be used as identifier');
            $row->width(6)->email('email', 'Email Address')
                ->placeholder('e.g. facilitator@example.com');
        });

        $form->row(function ($row) {
            $row->width(4)->text('national_id_number', 'National ID (NIN)');
        });

        // ── Location ─────────────────────────────────────────────────────
        $form->divider('Location');

        $form->row(function ($row) {
            $row->width(6)->select('district_name', 'District')
                ->options($this->northernUgandaDistricts());
            $row->width(6)->text('village', 'Village');
        });

        // ── Work information ──────────────────────────────────────────────
        $form->divider('Work Information');

        $form->row(function ($row) {
            $row->width(4)->date('facilitator_start_date', 'Start Date')
                ->required()
                ->help('Date the facilitator began field work');
            $row->width(4)->select('status', 'Account Status')
                ->options(['1' => 'Active', '0' => 'Inactive'])
                ->default('1')
                ->required();
        });

        // ── Account & Security ────────────────────────────────────────────
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

        // ── Saving logic ──────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            $form->user_type = 'Customer';

            // Build name safely — only if both parts are present
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
                    $form->username = 'fac_' . time();
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

                // Facilitators skip the member onboarding flow
                $form->onboarding_step = 'step_7_complete';
                $form->onboarding_completed_at = now();
            } else {
                if (empty($form->username) && !empty($form->phone_number)) {
                    $form->username = preg_replace('/[^0-9]/', '', $form->phone_number);
                } elseif (empty($form->username)) {
                    unset($form->username); // keep existing DB value, don't blank it
                }

                // Protect email from being wiped on update
                if (empty(trim((string) ($form->email ?? '')))) {
                    unset($form->email);
                }

                // Protect phone_number from being wiped on update
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
        $facilitator = User::findOrFail($id);

        if (!$this->verifyIpAccess($facilitator)) {
            return $this->denyIpAccess();
        }

        if (empty($facilitator->phone_number)) {
            admin_toastr('Facilitator has no phone number on file.', 'error');
            return redirect()->back();
        }

        $firstName = $facilitator->first_name ?: explode(' ', $facilitator->name)[0];
        $username  = $facilitator->username ?: $facilitator->phone_number;
        $password  = preg_replace('/[^0-9]/', '', $facilitator->phone_number);

        $message  = "FAO FFS-MIS — Login Credentials\n\n";
        $message .= "Dear {$firstName},\n";
        $message .= "Username: {$username}\n";
        $message .= "Password: {$password}\n\n";
        $message .= "Download the FAO FFS-MIS app from Play Store or contact your administrator.";

        try {
            \App\Models\Utils::send_sms($facilitator->phone_number, $message);
            admin_toastr("Credentials sent to {$facilitator->name} ({$facilitator->phone_number})", 'success');
            Log::info("FacilitatorController: credentials SMS sent to #{$facilitator->id}");
        } catch (\Exception $e) {
            admin_toastr('Failed to send SMS: ' . $e->getMessage(), 'error');
            Log::error("FacilitatorController: credentials SMS failed for #{$facilitator->id}: " . $e->getMessage());
        }

        return redirect()->back();
    }
}
