<?php

namespace App\Admin\Controllers;

use App\Models\FfsGroup;
use App\Models\Location;
use App\Models\User;
use App\Models\Project;
use App\Models\AccountTransaction;
use App\Models\VslaMeeting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FfsGroupController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Groups Management';

    /**
     * Get dynamic title based on URL
     */
    protected function title()
    {
        $url = url()->current();
        
        if (strpos($url, 'ffs-farmer-field-schools') !== false) {
            return 'Farmer Field Schools';
        }
        if (strpos($url, 'ffs-farmer-business-schools') !== false) {
            return 'Farmer Business Schools';
        }
        if (strpos($url, 'ffs-vslas') !== false) {
            return 'Village Savings & Loan Associations';
        }
        if (strpos($url, 'ffs-group-associations') !== false) {
            return 'Group Associations';
        }
        
        return 'Groups Management';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FfsGroup());
        
        // Detect group type from URL
        $url = url()->current();
        $groupType = null;
        
        if (strpos($url, 'ffs-farmer-field-schools') !== false) {
            $groupType = 'FFS';
        } elseif (strpos($url, 'ffs-farmer-business-schools') !== false) {
            $groupType = 'FBS';
        } elseif (strpos($url, 'ffs-vslas') !== false) {
            $groupType = 'VSLA';
        } elseif (strpos($url, 'ffs-group-associations') !== false) {
            $groupType = 'Association';
        }
        
        // Filter by group type if detected
        if ($groupType) {
            $grid->model()->where('type', $groupType);
        }

        // Disable batch actions and deletion
        $grid->disableBatchActions();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });
        
        // Filters
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            
            $filter->equal('type', 'Group Type')->select(FfsGroup::getTypes());
            $filter->equal('status', 'Status')->select(FfsGroup::getStatuses());
            $filter->equal('district_id', 'District')->select(Location::where('type', 'District')->pluck('name', 'id'));
            $filter->like('name', 'Group Name');
            $filter->equal('facilitator_id', 'Facilitator')->select(User::pluck('name', 'id'));
        });
        
        // Columns
        $grid->column('id', 'ID')->sortable()->hide();
        $grid->column('code', 'Code')->label('primary')->copyable()->sortable();
        
        $grid->column('name', 'Group Name')->display(function($name) {
            return "<strong>$name</strong>";
        })->sortable();
        
        $grid->column('type', 'Type')->label([
            'FFS' => 'primary',
            'FBS' => 'success',
            'VSLA' => 'warning',
            'Association' => 'info',
        ])->sortable();
        
        $grid->column('district_name', 'District')->display(function() {
            return $this->district ? $this->district->name : 'N/A';
        })->sortable();
        
        $grid->column('village', 'Village');
        
        $grid->column('total_members', 'Members')->display(function($total) {
            $male = $this->male_members ?? 0;
            $female = $this->female_members ?? 0;
            return "
                <span class='badge' style='background: #05179F; font-size: 13px;'>$total</span>
                <br>
                <small>
                    <span class='badge' style='background: #2196f3; font-size: 11px;'>♂ $male</span>
                    <span class='badge' style='background: #e91e63; font-size: 11px;'>♀ $female</span>
                </small>
            ";
        })->sortable();
        
        $grid->column('facilitator_name', 'Facilitator')->display(function() {
            return $this->facilitator ? $this->facilitator->name : '<span style="color: #999;">Not Assigned</span>';
        });
        
        // VSLA-specific columns
        if ($groupType === 'VSLA') {
            $grid->column('vsla_cycles', 'Active Cycles')->display(function() {
                $activeCycles = Project::where('group_id', $this->id)
                    ->where('is_vsla_cycle', 'Yes')
                    ->where('is_active_cycle', 'Yes')
                    ->count();
                return "<a href='/admin/cycles?group_id={$this->id}' style='color: #2196f3;'>{$activeCycles}</a>";
            });
            
            $grid->column('vsla_balance', 'Group Balance')->display(function() {
                // Get balance from all group transactions (user_id = null)
                // by checking transactions created during meetings for this group
                $balance = AccountTransaction::where('user_id', null)
                    ->where('created_by_id', '>', 0)
                    ->whereIn('source', ['share_purchase', 'loan_disbursement', 'loan_repayment', 'savings', 'welfare_contribution'])
                    ->sum('amount');
                    
                $formatted = number_format($balance, 0);
                $color = $balance >= 0 ? 'green' : 'red';
                return "<strong style='color: {$color};'>UGX {$formatted}</strong>";
            });
            
            $grid->column('total_meetings', 'Meetings')->display(function() {
                $meetings = VslaMeeting::where('group_id', $this->id)->count();
                return "<a href='/admin/vsla-meetings?group_id={$this->id}'>{$meetings}</a>";
            });
        }
        
        $grid->column('status', 'Status')->label([
            'Active' => 'success',
            'Inactive' => 'default',
            'Suspended' => 'warning',
            'Graduated' => 'info',
        ])->sortable();
        
        $grid->column('registration_date', 'Registered')->display(function($date) {
            return $date ? date('d M Y', strtotime($date)) : 'N/A';
        })->sortable();
        
        $grid->column('created_at', 'Created')->display(function($date) {
            return date('d M Y', strtotime($date));
        })->sortable()->hide();
        
        // Set default sort
        $grid->model()->orderBy('created_at', 'desc');
        
        // Quick create
        $grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
            $create->text('name', 'Group Name')->required();
            $create->select('type', 'Type')->options(FfsGroup::getTypes())->required();
            $create->select('district_id', 'District')->options(Location::where('type', 'District')->pluck('name', 'id'));
        });

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
        $show = new Show(FfsGroup::findOrFail($id));
        
        $show->panel()->style('primary')->title('Group Details');

        // Basic Information
        $show->divider('Basic Information');
        $show->field('code', 'Group Code')->label('primary');
        $show->field('name', 'Group Name');
        $show->field('type_text', 'Type');
        $show->field('status_text', 'Status');
        $show->field('registration_date', 'Registration Date')->as(function($date) {
            return $date ? date('d M Y', strtotime($date)) : 'N/A';
        });
        
        // Location
        $show->divider('Location Information');
        $show->field('district_name', 'District');
        $show->field('subcounty', 'Subcounty')->as(function() {
            return $this->subcounty ? $this->subcounty->name : 'N/A';
        });
        $show->field('parish', 'Parish')->as(function() {
            return $this->parish ? $this->parish->name : 'N/A';
        });
        $show->field('village', 'Village');
        $show->field('latitude', 'Latitude');
        $show->field('longitude', 'Longitude');
        
        // Meeting Details
        $show->divider('Meeting Details');
        $show->field('meeting_venue', 'Meeting Venue');
        $show->field('meeting_day', 'Meeting Day');
        $show->field('meeting_frequency', 'Frequency');
        
        // Value Chains
        $show->divider('Value Chains');
        $show->field('primary_value_chain', 'Primary Value Chain');
        $show->field('secondary_value_chains', 'Secondary Value Chains')->json();
        
        // Members
        $show->divider('Member Statistics');
        $show->field('total_members', 'Total Members')->badge('primary');
        $show->field('male_members', 'Male Members')->badge('info');
        $show->field('female_members', 'Female Members')->badge('danger');
        $show->field('youth_members', 'Youth Members (18-35)')->badge('warning');
        $show->field('pwd_members', 'Members with Disabilities')->badge('success');
        
        // Facilitation
        $show->divider('Facilitation');
        $show->field('facilitator_name', 'Facilitator');
        $show->field('contact_person_name', 'Contact Person');
        $show->field('contact_person_phone', 'Contact Phone');
        
        // Cycle Info (VSLA/FFS)
        $show->divider('Cycle Information');
        $show->field('cycle_number', 'Cycle Number');
        $show->field('cycle_start_date', 'Cycle Start Date')->as(function($date) {
            return $date ? date('d M Y', strtotime($date)) : 'N/A';
        });
        $show->field('cycle_end_date', 'Cycle End Date')->as(function($date) {
            return $date ? date('d M Y', strtotime($date)) : 'N/A';
        });
        
        // Additional Info
        $show->divider('Additional Information');
        $show->field('description', 'Description');
        $show->field('objectives', 'Objectives');
        $show->field('achievements', 'Achievements');
        $show->field('challenges', 'Challenges');
        
        // Photo
        $show->field('photo', 'Photo')->image();
        
        // Audit
        $show->divider('Audit Information');
        $show->field('created_by', 'Created By')->as(function() {
            return $this->createdBy ? $this->createdBy->name : 'System';
        });
        $show->field('created_at', 'Created At')->date('d M Y H:i:s');
        $show->field('updated_at', 'Updated At')->date('d M Y H:i:s');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new FfsGroup());
        
        // Detect group type from URL
        $url = url()->current();
        $groupType = null;
        
        if (strpos($url, 'ffs-farmer-field-schools') !== false) {
            $groupType = 'FFS';
        } elseif (strpos($url, 'ffs-farmer-business-schools') !== false) {
            $groupType = 'FBS';
        } elseif (strpos($url, 'ffs-vslas') !== false) {
            $groupType = 'VSLA';
        } elseif (strpos($url, 'ffs-group-associations') !== false) {
            $groupType = 'Other';
        }
        
        // Basic Information
        $form->row(function ($row) use ($groupType) {
            $row->width(8)->text('name', 'Group Name')->required();
            
            if ($groupType) {
                $row->width(4)->display('type_display', 'Type')->with(function() use ($groupType) {
                    return $groupType;
                });
                $form->hidden('type')->default($groupType);
            } else {
                $row->width(4)->select('type', 'Type')->options(FfsGroup::getTypes())->required();
            }
        });
        
        $form->row(function ($row) {
            $row->width(4)->date('registration_date', 'Registration Date')->default(date('Y-m-d'));
            $row->width(4)->select('status', 'Status')->options(FfsGroup::getStatuses())->default('Active');
            $row->width(4)->select('facilitator_id', 'Facilitator')->options(User::pluck('name', 'id'));
        });
        
        $form->divider();
        
        // Location
        $form->row(function ($row) {
            $row->width(6)->select('district_id', 'District')->options(Location::where('type', 'District')->pluck('name', 'id'))->required();
            $row->width(6)->text('village', 'Village');
        });
        
        // Meeting Details
        $form->row(function ($row) {
            $row->width(6)->select('meeting_day', 'Meeting Day')->options([
                'Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday',
                'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday', 'Sunday' => 'Sunday'
            ]);
            $row->width(6)->select('meeting_frequency', 'Frequency')->options(FfsGroup::getMeetingFrequencies())->default('Weekly');
        });
        
        $form->divider();
        
        // Value Chains
        $valueChains = [
            'Maize' => 'Maize', 'Beans' => 'Beans', 'Sorghum' => 'Sorghum', 'Millet' => 'Millet',
            'Groundnuts' => 'Groundnuts', 'Simsim' => 'Simsim', 'Cassava' => 'Cassava',
            'Sweet Potato' => 'Sweet Potato', 'Vegetables' => 'Vegetables', 'Fruits' => 'Fruits',
            'Poultry' => 'Poultry', 'Goats' => 'Goats', 'Cattle' => 'Cattle',
            'Beekeeping' => 'Beekeeping', 'Fish Farming' => 'Fish Farming'
        ];
        
        $form->row(function ($row) use ($valueChains) {
            $row->width(6)->select('primary_value_chain', 'Primary Value Chain')->options($valueChains)->required();
            $row->width(6)->multipleSelect('secondary_value_chains', 'Other Value Chains')->options($valueChains);
        });
        
        $form->divider();
        
        // Contact
        $form->row(function ($row) {
            $row->width(6)->text('contact_person_name', 'Contact Person');
            $row->width(6)->mobile('contact_person_phone', 'Contact Phone');
        });
        
        // Additional fields (collapsible)
        $form->row(function ($row) {
            $row->width(6)->textarea('description', 'Description')->rows(3);
            $row->width(6)->image('photo', 'Photo');
        });
        
        // Note: Code is auto-generated by FfsGroup model boot() method
        // Format: DISTRICT-TYPE-YEAR-NUMBER (e.g., KAM-FFS-25-0001)
        
        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
