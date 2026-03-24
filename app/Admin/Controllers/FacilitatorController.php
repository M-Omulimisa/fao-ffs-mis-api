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
        $grid->column('email',        'Email');
        $grid->column('sex',          'Gender');

        $grid->column('district_name', 'District')->display(function ($v) {
            return $v ?: '—';
        });

        if ($isSuperAdmin) {
            $grid->column('ip_id', 'Partner')->display(function ($id) {
                if (!$id) return '—';
                $ip = ImplementingPartner::find($id);
                return $ip ? e($ip->short_name ?: $ip->name) : "IP #{$id}";
            });
        }

        $grid->column('facilitator_start_date', 'Start Date')->date('d M Y')->sortable();

        $grid->column('groups_count', 'Groups')->display(function () {
            $count = DB::table('ffs_groups')
                ->where('facilitator_id', $this->id)
                ->count();
            if (!$count) return '<span class="label label-default">0</span>';
            return '<span class="label label-primary">' . $count . '</span>';
        });

        $grid->column('created_at', 'Registered')->date('d M Y')->sortable();

        return $grid;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────────

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

        $show = new Show($facilitator);

        $show->field('id',    'ID');

        $show->divider();
        $show->field('first_name', 'First Name');
        $show->field('last_name',  'Last Name');
        $show->field('name',       'Full Name');
        $show->field('sex',        'Gender');
        $show->field('phone_number','Phone');
        $show->field('email',      'Email');
        $show->field('username',   'Username');
        $show->field('national_id_number', 'National ID');

        $show->divider();
        $show->field('district_name', 'District');
        $show->field('village', 'Village');

        $show->divider();
        $show->field('ip_id', 'Implementing Partner')->as(function ($id) {
            $ip = ImplementingPartner::find($id);
            return $ip ? "{$ip->name} ({$ip->short_name})" : '—';
        });
        $show->field('facilitator_start_date', 'Facilitator Since');
        $show->field('status', 'Status')->as(function ($v) {
            return $v == 1 ? 'Active' : 'Inactive';
        });
        $show->field('created_at', 'Registered At');

        // Groups managed
        $show->field('id', 'Groups Managed')->as(function ($id) {
            $groups = DB::table('ffs_groups')
                ->where('facilitator_id', $id)
                ->pluck('name');
            return $groups->isNotEmpty()
                ? $groups->implode(', ')
                : 'None assigned';
        });

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
            $form->name      = trim($form->first_name . ' ' . $form->last_name);

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
