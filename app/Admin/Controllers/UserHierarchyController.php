<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;

class UserHierarchyController extends AdminController
{
    protected $title = 'User Hierarchy & Network';

    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->model()->orderBy('id', 'desc');

        $grid->disableBatchActions();
        $grid->disableCreateButton();
        $grid->disableExport();

        $grid->column('id', __('ID'))->sortable()->width(60);
        $grid->column('avatar', __('Photo'))->lightbox(['width' => 50, 'height' => 50])->width(60);

        $grid->column('full_name', __('Name & Phone'))
            ->display(function () {
                $name = trim($this->first_name . ' ' . $this->last_name);
                $phone = $this->phone_number ? '<br><small class="text-muted"><i class="fa fa-phone"></i> ' . $this->phone_number . '</small>' : '';
                return $name . $phone;
            })->sortable()->width(200);

        $grid->column('business_name', __('DIP ID'))->sortable()->width(100);

        $grid->column('dtehm_member_id', __('DTEHM ID'))
            ->display(function ($dtehmId) {
                if ($dtehmId) {
                    return "<span class='label label-success' style='font-size: 10px;'>$dtehmId</span>";
                }
                return "<span class='text-muted'>-</span>";
            })->width(110);

        $grid->column('sponsor_id', __('Sponsor ID'))
            ->display(function ($sponsorId) {
                if (empty($sponsorId)) {
                    return '<span class="text-muted">-</span>';
                }
                // Find sponsor to get their ID for filtering by parent_1
                $sponsor = \App\Models\User::where('business_name', $sponsorId)
                    ->orWhere('dtehm_member_id', $sponsorId)
                    ->first();

                if ($sponsor) {
                    $filterUrl = url('/admin/user-hierarchy?parent_1=' . $sponsor->id);
                    return '<a href="' . $filterUrl . '" class="label label-primary" style="font-size: 10px; cursor: pointer;" title="Click to view direct children">
                        ' . $sponsorId . '
                    </a>';
                }
                return '<span class="label label-primary" style="font-size: 10px;">' . $sponsorId . '</span>';
            })->width(100);

        $grid->column('downline_count', __('Total Downline'))
            ->display(function () {
                $total = $this->getTotalDownlineCount();
                return $total > 0 ? "<span class='badge bg-blue'>$total</span>" : '0';
            })->width(80);

        // Generation columns (Gen 1 to Gen 10)
        for ($i = 1; $i <= 10; $i++) {
            $grid->column("gen{$i}_count", __("Gen $i"))
                ->display(function () use ($i) {
                    $count = $this->getGenerationCount($i);
                    if ($count > 0) {
                        return "<span class='badge bg-green'>$count</span>";
                    }
                    return "<span class='text-muted'>0</span>";
                })->width(60);
        }

        $grid->column('view_tree', __('View Tree'))
            ->display(function () {
                $url = admin_url('user-hierarchy/' . $this->id);
                return '<a href="' . $url . '" target="_blank" class="btn btn-xs btn-primary" title="View Network Tree">
                    <i class="fa fa-sitemap"></i> Tree
                </a>';
            })->width(90);

        $grid->quickSearch('first_name', 'last_name', 'business_name', 'dtehm_member_id', 'phone_number')
            ->placeholder('Search by name, DIP ID, DTEHM ID, or phone');

        // Add filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            // Basic filters
            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('business_name', 'DIP ID');
            $filter->like('dtehm_member_id', 'DTEHM ID');
            $filter->like('sponsor_id', 'Sponsor ID')
                ->placeholder('e.g., DIP0001 or DTEHM20250001');

            // Parent hierarchy filters
            $filter->equal('parent_1', 'Direct Parent (Gen 1)')
                ->select(function () {
                    return \App\Models\User::whereNotNull('business_name')
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(function ($user) {
                            $label = $user->first_name . ' ' . $user->last_name . ' (' . $user->business_name . ')';
                            return [$user->id => $label];
                        });
                });

            // Membership filters
            $filter->equal('is_dtehm_member', 'DTEHM Member?')->radio([
                '' => 'All',
                'Yes' => 'Yes',
                'No' => 'No',
            ]);

            $filter->equal('is_dip_member', 'DIP Member?')->radio([
                '' => 'All',
                'Yes' => 'Yes',
                'No' => 'No',
            ]);

            $filter->equal('dtehm_membership_is_paid', 'DTEHM Paid?')->radio([
                '' => 'All',
                'Yes' => 'Paid',
                'No' => 'Unpaid',
            ]);

            // Gender filter
            $filter->equal('sex', 'Gender')->radio([
                '' => 'All',
                'Male' => 'Male',
                'Female' => 'Female',
            ]);

            // Country filter
            $filter->equal('country', 'Country')->select([
                'Uganda' => 'Uganda',
                'Kenya' => 'Kenya',
                'Tanzania' => 'Tanzania',
                'Rwanda' => 'Rwanda',
                'Burundi' => 'Burundi',
                'South Sudan' => 'South Sudan',
                'DRC' => 'DRC',
            ]);

            // Date filters
            $filter->between('created_at', 'Registration Date')->date();
            $filter->between('dtehm_member_membership_date', 'DTEHM Membership Date')->date();

            // Has downline filter
            $filter->where(function ($query) {
                $hasDownline = $this->input;
                if ($hasDownline === 'yes') {
                    $query->whereRaw('(
                        SELECT COUNT(*) FROM users as u2 
                        WHERE u2.sponsor_id = users.business_name 
                        OR u2.sponsor_id = users.dtehm_member_id
                    ) > 0');
                } elseif ($hasDownline === 'no') {
                    $query->whereRaw('(
                        SELECT COUNT(*) FROM users as u2 
                        WHERE u2.sponsor_id = users.business_name 
                        OR u2.sponsor_id = users.dtehm_member_id
                    ) = 0');
                }
            }, 'Has Downline?')->radio([
                '' => 'All',
                'yes' => 'Has Children',
                'no' => 'No Children',
            ]);
        });

        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $user = User::findOrFail($id);

        return view('admin.user-hierarchy.tree', compact('user'));
    }
}
