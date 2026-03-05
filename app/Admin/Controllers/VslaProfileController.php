<?php

namespace App\Admin\Controllers;

use App\Admin\Traits\IpScopeable;
use App\Models\FfsGroup;
use App\Models\Location;
use App\Models\Project;
use App\Models\User;
use App\Models\VslaProfile;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VslaProfileController extends AdminController
{
    use IpScopeable;

    protected $title = 'VSLA Profiles';

    // ─────────────────────────────────────────────
    //  GRID (list view)
    // ─────────────────────────────────────────────
    protected function grid()
    {
        $grid = new Grid(new VslaProfile());

        // IP scoping
        $this->applyIpScope($grid);

        $grid->model()->with(['group', 'chairperson', 'implementingPartner', 'district'])
            ->orderBy('id', 'desc');

        $grid->column('id', 'ID')->sortable();
        $grid->column('group_name', 'Group Name')->sortable();
        $grid->column('district.name', 'District');
        $grid->column('village', 'Village');
        $grid->column('meeting_frequency', 'Meeting');
        $grid->column('estimated_members', 'Members');
        $grid->column('cycle_name', 'Cycle');
        $grid->column('share_value', 'Share Value')->display(function ($val) {
            return $val ? number_format($val, 0) : '-';
        });
        $grid->column('saving_type', 'Saving Type');
        $grid->column('chair_first_name', 'Chairperson')->display(function () {
            $name = trim(($this->chair_first_name ?? '') . ' ' . ($this->chair_last_name ?? ''));
            return $name ?: '-';
        });
        $grid->column('chair_phone', 'Chair Phone');
        $grid->column('implementingPartner.short_name', 'IP');
        $grid->column('status', 'Status')->label([
            'Active'   => 'success',
            'Inactive' => 'danger',
        ]);
        $grid->column('created_at', 'Created')->sortable()->display(function ($val) {
            return $val ? date('d M Y', strtotime($val)) : '-';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('group_name', 'Group Name');
            $filter->equal('district_id', 'District')
                ->select(Location::where('type', 'district')
                    ->orderBy('name')
                    ->pluck('name', 'id'));
            $filter->equal('saving_type', 'Saving Type')->select([
                'shares'     => 'Shares',
                'any_amount' => 'Any Amount',
            ]);
            $filter->equal('status', 'Status')->select([
                'Active'   => 'Active',
                'Inactive' => 'Inactive',
            ]);
            $this->addIpFilter($filter);
        });

        $grid->disableExport();

        return $grid;
    }

    // ─────────────────────────────────────────────
    //  DETAIL (show view)
    // ─────────────────────────────────────────────
    protected function detail($id)
    {
        $show = new Show(VslaProfile::findOrFail($id));

        $show->divider('Group Information');
        $show->field('group_name', 'Group Name');
        $show->field('district.name', 'District');
        $show->field('village', 'Village');
        $show->field('registration_date', 'Registration Date');
        $show->field('meeting_frequency', 'Meeting Frequency');
        $show->field('meeting_day', 'Meeting Day');
        $show->field('meeting_venue', 'Meeting Venue');
        $show->field('estimated_members', 'Estimated Members');

        $show->divider('Savings Cycle Configuration');
        $show->field('cycle_name', 'Cycle Name');
        $show->field('saving_type', 'Saving Type');
        $show->field('share_value', 'Share Value (UGX)')->as(function ($val) {
            return $val ? number_format($val, 0) : '-';
        });
        $show->field('loan_interest_rate', 'Loan Interest Rate (%)');
        $show->field('interest_frequency', 'Interest Frequency');
        $show->field('minimum_loan_amount', 'Minimum Loan Amount (UGX)')->as(function ($val) {
            return $val ? number_format($val, 0) : '-';
        });
        $show->field('maximum_loan_multiple', 'Maximum Loan Multiple');
        $show->field('late_payment_penalty', 'Late Payment Penalty (%)');
        $show->field('cycle_start_date', 'Cycle Start');
        $show->field('cycle_end_date', 'Cycle End');

        $show->divider('Chairperson');
        $show->field('chair_first_name', 'First Name');
        $show->field('chair_last_name', 'Last Name');
        $show->field('chair_phone', 'Phone');
        $show->field('chair_sex', 'Gender');
        $show->field('chair_email', 'Email');
        $show->field('chair_national_id', 'National ID (NIN)');

        $show->divider('System Links');
        $show->field('group.name', 'Linked Group');
        $show->field('cycle.cycle_name', 'Linked Cycle');
        $show->field('chairperson.name', 'Linked Chairperson User');
        $show->field('implementingPartner.name', 'Implementing Partner');
        $show->field('status', 'Status');
        $show->field('created_at', 'Created');
        $show->field('updated_at', 'Updated');

        return $show;
    }

    // ─────────────────────────────────────────────
    //  FORM (create / edit)
    // ─────────────────────────────────────────────
    protected function form()
    {
        $form = new Form(new VslaProfile());

        $year = date('Y');

        // ══════════════════════════════════════════
        //  Section 1: VSLA Group Information
        // ══════════════════════════════════════════
        $form->divider('VSLA Group Information');

        // IP field (select dropdown via IpScopeable)
        $this->addIpFieldToForm($form);

        $form->text('group_name', 'Group Name')
            ->rules('required')
            ->help('Name of the VSLA group');

        $form->select('district_id', 'District')
            ->options(
                Location::where('type', 'district')
                    ->orderBy('name')
                    ->pluck('name', 'id')
            )
            ->help('District where the group is located');

        $form->text('village', 'Village');

        $form->date('registration_date', 'Registration Date')
            ->default(date('Y-m-d'))
            ->help('Date the group was registered');

        $form->select('meeting_frequency', 'Meeting Frequency')
            ->options(FfsGroup::getMeetingFrequencies())
            ->default('Weekly');

        $form->select('meeting_day', 'Meeting Day')
            ->options([
                'Monday'    => 'Monday',
                'Tuesday'   => 'Tuesday',
                'Wednesday' => 'Wednesday',
                'Thursday'  => 'Thursday',
                'Friday'    => 'Friday',
                'Saturday'  => 'Saturday',
                'Sunday'    => 'Sunday',
            ])
            ->default('Saturday');

        $form->text('meeting_venue', 'Meeting Venue')
            ->help('Where the group meets (e.g. community center, church, under a tree)');

        $form->number('estimated_members', 'Estimated Members')
            ->default(25)
            ->help('Approximate number of members in this group');

        // ══════════════════════════════════════════
        //  Section 2: Savings Cycle Configuration
        // ══════════════════════════════════════════
        $form->divider('Savings Cycle Configuration');

        $form->text('cycle_name', 'Cycle Name')
            ->default("Cycle 1 ({$year})")
            ->help('Name for this savings cycle. Auto-generated if left blank.');

        $form->select('saving_type', 'Saving Type')
            ->options([
                'shares'     => 'Shares (fixed share value)',
                'any_amount' => 'Any Amount (flexible savings)',
            ])
            ->default('shares')
            ->help('How members contribute savings');

        $form->currency('share_value', 'Share Value (UGX)')
            ->symbol('UGX')
            ->default(5000)
            ->help('Cost per share in Uganda Shillings');

        $form->decimal('loan_interest_rate', 'Loan Interest Rate (%)')
            ->default(10)
            ->help('Interest rate on loans');

        $form->select('interest_frequency', 'Interest Frequency')
            ->options([
                'Weekly'  => 'Weekly',
                'Monthly' => 'Monthly',
            ])
            ->default('Monthly')
            ->help('How often interest is applied');

        $form->currency('minimum_loan_amount', 'Minimum Loan Amount (UGX)')
            ->symbol('UGX')
            ->default(10000)
            ->help('Smallest loan a member can take');

        $form->decimal('maximum_loan_multiple', 'Maximum Loan Multiple')
            ->default(3)
            ->help('Maximum loan = this multiple × member\'s total savings');

        $form->decimal('late_payment_penalty', 'Late Payment Penalty (%)')
            ->default(5)
            ->help('Penalty percentage for late loan repayments');

        $form->date('cycle_start_date', 'Cycle Start Date')
            ->default(date('Y-m-d'))
            ->help('When the savings cycle begins');

        $form->date('cycle_end_date', 'Cycle End Date')
            ->default(date('Y-12-31'))
            ->help('When the savings cycle ends');

        // ══════════════════════════════════════════
        //  Section 3: Chairperson Details
        // ══════════════════════════════════════════
        $form->divider('Chairperson Details');

        $form->text('chair_first_name', 'First Name')
            ->help('Chairperson first name');

        $form->text('chair_last_name', 'Last Name')
            ->help('Chairperson last name');

        $form->select('chair_sex', 'Gender')
            ->options(['Male' => 'Male', 'Female' => 'Female']);

        $form->text('chair_phone', 'Phone Number')
            ->help('Chairperson phone number (used as login credential)');

        $form->email('chair_email', 'Email Address')
            ->help('Optional email for the chairperson');

        $form->text('chair_national_id', 'National ID (NIN)')
            ->help('National Identification Number');

        // ══════════════════════════════════════════
        //  Status
        // ══════════════════════════════════════════
        $form->divider('Status');
        $form->select('status', 'Status')
            ->options(['Active' => 'Active', 'Inactive' => 'Inactive'])
            ->default('Active');

        // ── Hidden linkage fields ──
        $form->hidden('group_id');
        $form->hidden('cycle_id');
        $form->hidden('chairperson_id');

        // ─────────────────────────────────────────────
        //  SAVING CALLBACK — auto-generate linked records
        // ─────────────────────────────────────────────
        $form->saved(function (Form $form) {
            $profile = $form->model();
            $isCreating = $form->isCreating();
            $year = date('Y');

            // ──────────────────────────────────────
            //  1. Create or Update the FfsGroup
            // ──────────────────────────────────────
            if ($profile->group_id) {
                $group = FfsGroup::find($profile->group_id);
                if ($group) {
                    $group->update([
                        'name'              => $profile->group_name ?? $group->name,
                        'district_id'       => $profile->district_id ?? $group->district_id,
                        'village'           => $profile->village ?? $group->village,
                        'meeting_frequency' => $profile->meeting_frequency ?? $group->meeting_frequency,
                        'meeting_day'       => $profile->meeting_day ?? $group->meeting_day,
                        'meeting_venue'     => $profile->meeting_venue ?? $group->meeting_venue,
                        'estimated_members' => $profile->estimated_members ?? $group->estimated_members,
                        'registration_date' => $profile->registration_date ?? $group->registration_date,
                        'ip_id'             => $profile->ip_id ?? $group->ip_id,
                    ]);
                }
            } else {
                $group = new FfsGroup();
                $group->name              = $profile->group_name;
                $group->type              = 'VSLA';
                $group->district_id       = $profile->district_id;
                $group->village           = $profile->village;
                $group->meeting_frequency = $profile->meeting_frequency ?? 'Weekly';
                $group->meeting_day       = $profile->meeting_day ?? 'Saturday';
                $group->meeting_venue     = $profile->meeting_venue;
                $group->estimated_members = $profile->estimated_members ?? 25;
                $group->registration_date = $profile->registration_date ?? now();
                $group->ip_id             = $profile->ip_id;
                $group->status            = 'Active';
                $group->cycle_number      = 1;
                $group->cycle_start_date  = $profile->cycle_start_date ?? date('Y-m-d');
                $group->cycle_end_date    = $profile->cycle_end_date ?? date('Y-12-31');
                $group->created_by_id     = Admin::user()->id ?? null;
                $group->save();

                $profile->group_id = $group->id;
            }

            // ──────────────────────────────────────
            //  2. Create or Update the Cycle (Project)
            // ──────────────────────────────────────
            $cycleName = $profile->cycle_name ?: substr("{$profile->group_name} – Cycle 1 ({$year})", 0, 200);

            if ($profile->cycle_id) {
                $cycle = Project::find($profile->cycle_id);
                if ($cycle) {
                    $cycle->update([
                        'cycle_name'           => $cycleName,
                        'title'                => $cycleName,
                        'saving_type'          => $profile->saving_type ?? $cycle->saving_type,
                        'share_value'          => $profile->share_value ?? $cycle->share_value,
                        'loan_interest_rate'   => $profile->loan_interest_rate ?? $cycle->loan_interest_rate,
                        'interest_frequency'   => $profile->interest_frequency ?? $cycle->interest_frequency,
                        'minimum_loan_amount'  => $profile->minimum_loan_amount ?? $cycle->minimum_loan_amount,
                        'maximum_loan_multiple' => (int) ($profile->maximum_loan_multiple ?? $cycle->maximum_loan_multiple),
                        'late_payment_penalty' => $profile->late_payment_penalty ?? $cycle->late_payment_penalty,
                        'start_date'           => $profile->cycle_start_date ?? $cycle->start_date,
                        'end_date'             => $profile->cycle_end_date ?? $cycle->end_date,
                        'meeting_frequency'    => $profile->meeting_frequency ?? $cycle->meeting_frequency,
                        'group_id'             => $group->id ?? $cycle->group_id,
                    ]);
                }
            } else {
                $cycle = new Project();
                $cycle->is_vsla_cycle        = 'Yes';
                $cycle->is_active_cycle      = 'Yes';
                $cycle->group_id             = $group->id;
                $cycle->cycle_name           = $cycleName;
                $cycle->title                = $cycleName;
                $cycle->description          = "Savings cycle auto-created via VSLA Profile for {$profile->group_name}.";
                $cycle->status               = 'ongoing';
                $cycle->saving_type          = $profile->saving_type ?? 'shares';
                $cycle->start_date           = $profile->cycle_start_date ?? date('Y-m-d');
                $cycle->end_date             = $profile->cycle_end_date ?? date('Y-12-31');
                $cycle->share_value          = $profile->share_value ?? 5000;
                $cycle->meeting_frequency    = $profile->meeting_frequency ?? 'Weekly';
                $cycle->loan_interest_rate   = $profile->loan_interest_rate ?? 10;
                $cycle->interest_frequency   = $profile->interest_frequency ?? 'Monthly';
                $cycle->minimum_loan_amount  = $profile->minimum_loan_amount ?? 10000;
                $cycle->maximum_loan_multiple = (int) ($profile->maximum_loan_multiple ?? 3);
                $cycle->late_payment_penalty = $profile->late_payment_penalty ?? 5;
                $cycle->created_by_id        = Admin::user()->id ?? null;
                $cycle->save();

                $profile->cycle_id = $cycle->id;
            }

            // ──────────────────────────────────────
            //  3. Create or Update the Chairperson (User)
            // ──────────────────────────────────────
            $hasChairInfo = !empty($profile->chair_first_name) || !empty($profile->chair_phone);

            if ($hasChairInfo) {
                // Pre-normalize phone to +256 format to avoid double-prefix from User model boot
                $rawPhone = $profile->chair_phone;
                $phone = null;
                if (!empty($rawPhone)) {
                    $phone = preg_replace('/[\s\-\(\)]+/', '', trim($rawPhone));
                    $phone = ltrim($phone, '0');
                    if (substr($phone, 0, 3) === '256') {
                        $phone = '+' . $phone;
                    } elseif (substr($phone, 0, 4) === '+256') {
                        // Already correct
                    } elseif (strlen($phone) === 9 && is_numeric($phone)) {
                        $phone = '+256' . $phone;
                    } else {
                        // For non-Ugandan or already-prefixed numbers, keep as-is
                        // but don't let User model add +256 again
                        if (substr($phone, 0, 1) !== '+') {
                            $phone = '+256' . $phone;
                        }
                    }
                }

                if ($profile->chairperson_id) {
                    // Update existing chairperson
                    $chair = User::find($profile->chairperson_id);
                    if ($chair) {
                        $updateData = [];
                        if (!empty($profile->chair_first_name)) $updateData['first_name'] = $profile->chair_first_name;
                        if (!empty($profile->chair_last_name))  $updateData['last_name']  = $profile->chair_last_name;
                        if (!empty($phone))                     $updateData['phone_number'] = $phone;
                        if (!empty($profile->chair_sex))        $updateData['sex'] = $profile->chair_sex;
                        if (!empty($profile->chair_email))      $updateData['email'] = $profile->chair_email;
                        if (!empty($profile->chair_national_id)) $updateData['national_id_number'] = $profile->chair_national_id;
                        $updateData['name'] = trim(($profile->chair_first_name ?? $chair->first_name) . ' ' . ($profile->chair_last_name ?? $chair->last_name));
                        $updateData['group_id'] = $group->id;
                        $updateData['district_id'] = $profile->district_id ?? $chair->district_id;
                        $updateData['village'] = $profile->village ?? $chair->village;
                        $chair->update($updateData);
                    }
                } else {
                    // Create new chairperson user
                    $firstName = $profile->chair_first_name ?? 'Chairperson';
                    $lastName  = $profile->chair_last_name ?? '';

                    // Check if a user with this phone already exists
                    $existingUser = null;
                    if ($phone) {
                        $existingUser = User::where('phone_number', $phone)
                            ->orWhere('phone_number', $rawPhone)
                            ->first();
                    }

                    if ($existingUser) {
                        $chair = $existingUser;
                        $chair->update([
                            'is_group_admin' => 'Yes',
                            'group_id'       => $group->id,
                        ]);
                    } else {
                        $chair = new User();
                        $chair->first_name         = $firstName;
                        $chair->last_name          = $lastName;
                        $chair->name               = trim($firstName . ' ' . $lastName);
                        $chair->phone_number       = $phone;
                        $chair->sex                = $profile->chair_sex;
                        $chair->email              = !empty($profile->chair_email) ? $profile->chair_email : null;
                        $chair->national_id_number = !empty($profile->chair_national_id) ? $profile->chair_national_id : null;
                        $chair->group_id           = $group->id;
                        $chair->ip_id              = $profile->ip_id;
                        $chair->district_id        = $profile->district_id;
                        $chair->village            = !empty($profile->village) ? $profile->village : null;
                        $chair->is_group_admin     = 'Yes';
                        $chair->user_type          = 'Customer';
                        $chair->status             = 'Active';
                        $chair->onboarding_step    = 'step_7_complete';

                        // Username & password default to phone digits (last 9 digits)
                        $digits = preg_replace('/[^0-9]/', '', $phone ?? '');
                        $digits = substr($digits, -9); // Use last 9 digits for clean username
                        $chair->username = $digits ?: ('chair_' . time());
                        $chair->password = bcrypt($digits ?: 'password');

                        $chair->save();
                    }

                    $profile->chairperson_id = $chair->id;
                }

                // Also set the group's admin_id to the chairperson
                if (isset($group) && $group->id && isset($chair) && $chair->id) {
                    FfsGroup::where('id', $group->id)->update(['admin_id' => $chair->id]);
                }
            }

            // ──────────────────────────────────────
            //  4. Persist linkage FKs back to profile
            // ──────────────────────────────────────
            $profile->saveQuietly();

            $statusMsg = $isCreating
                ? "VSLA Profile created! Group, savings cycle, and chairperson have been auto-generated."
                : "VSLA Profile updated. Linked group, cycle, and chairperson records have been synced.";

            admin_toastr($statusMsg, 'success');
        });

        // ── Form UI tweaks ──
        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
