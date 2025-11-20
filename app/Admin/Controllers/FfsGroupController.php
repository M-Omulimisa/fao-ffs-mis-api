<?php

namespace App\Admin\Controllers;

use App\Models\FfsGroup;
use App\Models\Location;
use App\Models\User;
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
            $groupType = 'Association';
        }
        
        // Basic Information
        $form->divider('Basic Information');
        
        $form->row(function ($row) use ($groupType) {
            $row->width(6)->text('name', 'Group Name')->required();
            
            if ($groupType) {
                $row->width(6)->display('type_display', 'Group Type')->with(function() use ($groupType) {
                    return $groupType;
                });
            } else {
                $row->width(6)->select('type', 'Group Type')->options(FfsGroup::getTypes())->required()->default('FFS');
            }
        });
        
        if ($groupType) {
            $form->hidden('type')->default($groupType);
        }
        
        $form->row(function ($row) {
            $row->width(4)->text('code', 'Group Code')->help('Leave blank to auto-generate');
            $row->width(4)->date('registration_date', 'Registration Date')->default(date('Y-m-d'));
            $row->width(4)->select('status', 'Status')->options(FfsGroup::getStatuses())->default('Active');
        });
        
        // Location
        $form->divider('Location Information');
        
        $districts = Location::where('type', 'District')->pluck('name', 'id');
        $form->row(function ($row) use ($districts) {
            $row->width(6)->select('district_id', 'District')->options($districts);
            $row->width(6)->select('subcounty_id', 'Subcounty');
        });
        
        $form->row(function ($row) {
            $row->width(6)->select('parish_id', 'Parish');
            $row->width(6)->text('village', 'Village');
        });
        
        $form->row(function ($row) {
            $row->width(6)->decimal('latitude', 'Latitude')->help('GPS coordinate');
            $row->width(6)->decimal('longitude', 'Longitude')->help('GPS coordinate');
        });
        
        // Meeting Details
        $form->divider('Meeting Details');
        
        $form->row(function ($row) {
            $row->width(6)->text('meeting_venue', 'Meeting Venue');
            $row->width(6)->select('meeting_day', 'Meeting Day')->options([
                'Monday' => 'Monday',
                'Tuesday' => 'Tuesday',
                'Wednesday' => 'Wednesday',
                'Thursday' => 'Thursday',
                'Friday' => 'Friday',
                'Saturday' => 'Saturday',
                'Sunday' => 'Sunday',
            ]);
        });
        
        $form->row(function ($row) {
            $row->width(6)->select('meeting_frequency', 'Meeting Frequency')->options(FfsGroup::getMeetingFrequencies())->default('Weekly');
        });
        
        // Value Chains
        $form->divider('Value Chains');
        
        $valueChains = [
            'Maize' => 'Maize',
            'Beans' => 'Beans',
            'Sorghum' => 'Sorghum',
            'Millet' => 'Millet',
            'Groundnuts' => 'Groundnuts',
            'Simsim' => 'Simsim',
            'Cassava' => 'Cassava',
            'Sweet Potato' => 'Sweet Potato',
            'Vegetables' => 'Vegetables',
            'Fruits' => 'Fruits',
            'Poultry' => 'Poultry',
            'Goats' => 'Goats',
            'Cattle' => 'Cattle',
            'Beekeeping' => 'Beekeeping',
            'Fish Farming' => 'Fish Farming',
        ];
        
        $form->row(function ($row) use ($valueChains) {
            $row->width(6)->select('primary_value_chain', 'Primary Value Chain')->options($valueChains)->required();
            $row->width(6)->multipleSelect('secondary_value_chains', 'Secondary Value Chains')->options($valueChains);
        });
        
        // Members Statistics
        $form->divider('Member Statistics');
        
        $form->row(function ($row) {
            $row->width(4)->decimal('total_members', 'Total Members')->default(0);
            $row->width(4)->decimal('male_members', 'Male Members')->default(0);
            $row->width(4)->decimal('female_members', 'Female Members')->default(0);
        });
        
        $form->row(function ($row) {
            $row->width(4)->decimal('youth_members', 'Youth Members (18-35)')->default(0);
            $row->width(4)->decimal('pwd_members', 'PWD Members')->default(0);
        });
        
        // Facilitation
        $form->divider('Facilitation & Contact');
        
        $facilitators = User::pluck('name', 'id');
        $form->row(function ($row) use ($facilitators) {
            $row->width(6)->select('facilitator_id', 'Facilitator')->options($facilitators);
            $row->width(6)->text('contact_person_name', 'Contact Person');
        });
        
        $form->row(function ($row) {
            $row->width(6)->mobile('contact_person_phone', 'Contact Phone');
        });
        
        // Cycle Information (for VSLA/FFS)
        $form->divider('Cycle Information');
        
        $form->row(function ($row) {
            $row->width(4)->decimal('cycle_number', 'Cycle Number')->default(1)->help('Current cycle');
            $row->width(4)->date('cycle_start_date', 'Cycle Start Date');
            $row->width(4)->date('cycle_end_date', 'Cycle End Date');
        });
        
        // Additional Information
        $form->divider('Additional Information');
        
        $form->row(function ($row) {
            $row->width(6)->textarea('description', 'Description')->rows(2);
            $row->width(6)->textarea('objectives', 'Objectives')->rows(2);
        });
        
        $form->row(function ($row) {
            $row->width(6)->textarea('achievements', 'Achievements')->rows(2);
            $row->width(6)->textarea('challenges', 'Challenges')->rows(2);
        });
        
        $form->row(function ($row) {
            $row->width(6)->image('photo', 'Group Photo');
        });
        
        // Form configuration
        $form->disableViewCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
