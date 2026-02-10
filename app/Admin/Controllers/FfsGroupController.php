<?php

namespace App\Admin\Controllers;

use App\Models\FfsGroup;
use App\Models\Location;
use App\Models\User;
use App\Models\Project;
use App\Models\AccountTransaction;
use App\Models\VslaMeeting;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FfsGroupController extends AdminController
{
    use IpScopeable;
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

        // ── IP Scoping: IP admins see only their own groups ──
        $this->applyIpScope($grid);

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
            $this->addIpFilter($filter);
            $filter->equal('ip_name', 'IP (Legacy)')->select(
                FfsGroup::whereNotNull('ip_name')->distinct()->pluck('ip_name', 'ip_name')
            );
            $filter->equal('district_id', 'District')->select(Location::where('type', 'District')->pluck('name', 'id'));
            $filter->like('district_text', 'District (Text)');
            $filter->like('name', 'Group Name');
            $filter->like('primary_value_chain', 'Value Chain');
            $filter->equal('facilitator_id', 'Facilitator')->select(User::pluck('name', 'id'));
            $filter->between('establishment_date', 'Established')->date();
        });
        
        // ========== PRIMARY COLUMNS (Always Visible) ==========
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
        
        // Implementing Partner (relational)
        $grid->column('ip_id', 'IP')->display(function () {
            if ($this->ip_id && $this->implementingPartner) {
                $name = $this->implementingPartner->short_name ?: $this->implementingPartner->name;
                return \"<span class='label label-primary'>{$name}</span>\";
            }
            // Fallback to legacy ip_name text
            if (!empty($this->ip_name)) {
                return \"<span class='label label-default'>{$this->ip_name}</span>\";
            }
            return '<span style=\"color:#999;\">-</span>';
        })->sortable();
        
        // District - Show text if no ID linked
        $grid->column('district_display', 'District')->display(function() {
            if ($this->district) {
                return $this->district->name;
            }
            return $this->district_text ?: '<span style="color: #999;">-</span>';
        })->sortable('district_text');
        
        // Subcounty
        $grid->column('subcounty_text', 'Subcounty')->display(function($subcounty) {
            return $subcounty ?: '<span style="color: #999;">-</span>';
        })->sortable();
        
        // Members with breakdown
        $grid->column('total_members', 'Members')->display(function($total) {
            $total = $total ?? 0;
            $male = $this->male_members ?? 0;
            $female = $this->female_members ?? 0;
            $pwd = ($this->pwd_male_members ?? 0) + ($this->pwd_female_members ?? 0);
            
            $html = "<span class='badge' style='background: #05179F; font-size: 13px;'>{$total}</span><br><small>";
            $html .= "<span class='badge' style='background: #2196f3; font-size: 10px;'>♂ {$male}</span> ";
            $html .= "<span class='badge' style='background: #e91e63; font-size: 10px;'>♀ {$female}</span>";
            if ($pwd > 0) {
                $html .= " <span class='badge' style='background: #ff9800; font-size: 10px;'>PWD {$pwd}</span>";
            }
            $html .= "</small>";
            return $html;
        })->sortable();
        
        // Primary Value Chain - KEY FIELD
        $grid->column('primary_value_chain', 'Primary Activity')->display(function($vc) {
            if (empty($vc)) return '<span style="color: #999;">-</span>';
            // Truncate long value chains
            $display = strlen($vc) > 25 ? substr($vc, 0, 22) . '...' : $vc;
            return "<span title='{$vc}' style='cursor: help;'>{$display}</span>";
        })->sortable();
        
        // Establishment Year
        $grid->column('establishment_date', 'Est.')->display(function($date) {
            if (empty($date)) return '<span style="color: #999;">-</span>';
            return date('Y', strtotime($date));
        })->sortable();
        
        // Project Code - visible by default
        $grid->column('project_code', 'Project')->display(function($code) {
            return $code ?: '-';
        })->sortable();
        
        // Source File - visible by default
        $grid->column('source_file', 'Source')->display(function($file) {
            if (empty($file)) return '-';
            if (strpos($file, 'KADP') !== false) return '<span class="badge" style="background:#28a745;">KADP</span>';
            if (strpos($file, 'ECO') !== false) return '<span class="badge" style="background:#17a2b8;">ECO</span>';
            if (strpos($file, 'GARD') !== false) return '<span class="badge" style="background:#6c757d;">GARD</span>';
            return $file;
        })->sortable();
        
        // ========== SECONDARY COLUMNS (Hidden by Default) ==========
        // These have less data or are less frequently needed
        
        $grid->column('village', 'Village')->hide();
        
        $grid->column('parish_text', 'Parish')->display(function($parish) {
            return $parish ?: '-';
        })->hide();
        
        $grid->column('facilitator_display', 'Facilitator')->display(function() {
            if ($this->facilitator) {
                return $this->facilitator->name;
            }
            return $this->contact_person_name ?: '<span style="color: #999;">-</span>';
        })->hide();
        
        $grid->column('contact_person_phone', 'Contact')->display(function($phone) {
            return $phone ?: '-';
        })->hide();
        
        $grid->column('facilitator_sex', 'Facilitator Gender')->display(function($sex) {
            if (empty($sex)) return '-';
            return $sex === 'Male' || $sex === 'M' ? '♂ Male' : '♀ Female';
        })->hide();
        
        $grid->column('secondary_value_chains', 'Other Activities')->display(function($chains) {
            if (empty($chains)) return '-';
            $decoded = is_string($chains) ? json_decode($chains, true) : $chains;
            if (empty($decoded)) return '-';
            return implode(', ', array_slice($decoded, 0, 2)) . (count($decoded) > 2 ? '...' : '');
        })->hide();
        
        $grid->column('latitude', 'Lat')->display(function($lat) {
            return $lat ? number_format($lat, 4) : '-';
        })->hide();
        
        $grid->column('longitude', 'Long')->display(function($lng) {
            return $lng ? number_format($lng, 4) : '-';
        })->hide();
        
        $grid->column('registration_date', 'Registered')->display(function($date) {
            return $date ? date('d M Y', strtotime($date)) : '-';
        })->sortable()->hide();
        
        $grid->column('status', 'Status')->label([
            'Active' => 'success',
            'Inactive' => 'default',
            'Suspended' => 'warning',
            'Graduated' => 'info',
        ])->hide();
        
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
        
        $grid->column('created_at', 'Created')->display(function($date) {
            return date('d M Y', strtotime($date));
        })->sortable()->hide();
        
        // Set default sort - by name for better usability
        $grid->model()->orderBy('name', 'asc');

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
        $show->field('establishment_date', 'Established')->as(function($date) {
            return $date ? date('d M Y', strtotime($date)) : 'N/A';
        });
        $show->field('registration_date', 'Registration Date')->as(function($date) {
            return $date ? date('d M Y', strtotime($date)) : 'N/A';
        });
        
        // Project/Partner Information
        $show->divider('Project Information');
        $show->field('ip_name', 'Implementing Partner')->badge('info');
        $show->field('project_code', 'Project Code');
        $show->field('loa', 'Letter of Agreement');
        $show->field('source_file', 'Import Source');
        
        // Location
        $show->divider('Location Information');
        $show->field('district_display', 'District')->as(function() {
            if ($this->district) return $this->district->name;
            return $this->district_text ?: 'N/A';
        });
        $show->field('subcounty_text', 'Subcounty');
        $show->field('parish_text', 'Parish');
        $show->field('village', 'Village');
        $show->field('latitude', 'Latitude');
        $show->field('longitude', 'Longitude');
        
        // Meeting Details
        $show->divider('Meeting Details');
        $show->field('meeting_venue', 'Meeting Venue');
        $show->field('meeting_day', 'Meeting Day');
        $show->field('meeting_frequency', 'Frequency');
        
        // Value Chains
        $show->divider('Value Chains / Activities');
        $show->field('primary_value_chain', 'Primary Value Chain');
        $show->field('secondary_value_chains', 'Secondary Value Chains')->as(function($chains) {
            if (empty($chains)) return 'N/A';
            $decoded = is_string($chains) ? json_decode($chains, true) : $chains;
            return is_array($decoded) ? implode(', ', $decoded) : $chains;
        });
        
        // Members
        $show->divider('Member Statistics');
        $show->field('total_members', 'Total Members')->badge('primary');
        $show->field('male_members', 'Male Members')->badge('info');
        $show->field('female_members', 'Female Members')->badge('danger');
        $show->field('youth_members', 'Youth Members (18-35)')->badge('warning');
        $show->field('pwd_members', 'PWD Members (Total)')->badge('success');
        $show->field('pwd_male_members', 'PWD Male Members');
        $show->field('pwd_female_members', 'PWD Female Members');
        
        // Facilitation
        $show->divider('Facilitation');
        $show->field('facilitator_name', 'Facilitator');
        $show->field('facilitator_sex', 'Facilitator Gender');
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
        $show->field('original_id', 'Original ID (Import)');
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
        
        // ========== BASIC INFORMATION ==========
        $form->row(function ($row) use ($groupType) {
            $row->width(6)->text('name', 'Group Name')->required();
            
            if ($groupType) {
                $row->width(3)->display('type_display', 'Type')->with(function() use ($groupType) {
                    return $groupType;
                });
            } else {
                $row->width(3)->select('type', 'Type')->options(FfsGroup::getTypes())->default('FFS');
            }
            $row->width(3)->select('status', 'Status')->options(FfsGroup::getStatuses())->default('Active');
        });
        
        // Hidden type field when detected from URL
        if ($groupType) {
            $form->hidden('type')->default($groupType);
        }
        
        // Project/Partner Info
        $form->row(function ($row) {
            $row->width(4)->select('ip_id', 'Implementing Partner')->options(
                ImplementingPartner::getDropdownOptions()
            );
            $row->width(4)->text('project_code', 'Project Code')->placeholder('e.g. UNJP/UGA/068/EC');
            $row->width(4)->date('establishment_date', 'Establishment Date');
        });
        
        $form->divider('Location');
        
        // Location
        $form->row(function ($row) {
            $row->width(4)->select('district_id', 'District')->options(
                Location::where('type', 'District')->pluck('name', 'id')
            );
            $row->width(4)->text('district_text', 'District (Text)')->placeholder('If not in list');
            $row->width(4)->text('subcounty_text', 'Subcounty');
        });
        
        $form->row(function ($row) {
            $row->width(4)->text('parish_text', 'Parish');
            $row->width(4)->text('village', 'Village');
            $row->width(4)->text('meeting_venue', 'Meeting Venue');
        });
        
        // GPS (optional)
        $form->row(function ($row) {
            $row->width(6)->decimal('latitude', 'Latitude')->placeholder('e.g. 2.1234');
            $row->width(6)->decimal('longitude', 'Longitude')->placeholder('e.g. 32.5678');
        });
        
        $form->divider('Activities / Value Chains');
        
        // Value Chains - use text field for flexibility
        $form->row(function ($row) {
            $row->width(6)->text('primary_value_chain', 'Primary Activity/Value Chain')
                ->placeholder('e.g. GOAT REARING, VEGETABLE GROWING');
            $row->width(6)->tags('secondary_value_chains', 'Other Activities')
                ->placeholder('Add multiple activities');
        });
        
        $form->divider('Membership');
        
        // Members
        $form->row(function ($row) {
            $row->width(3)->number('male_members', 'Male Members')->default(0);
            $row->width(3)->number('female_members', 'Female Members')->default(0);
            $row->width(3)->number('pwd_male_members', 'PWD Males')->default(0)->help('Persons with Disabilities');
            $row->width(3)->number('pwd_female_members', 'PWD Females')->default(0);
        });
        
        $form->row(function ($row) {
            $row->width(4)->number('total_members', 'Total Members')->default(0);
            $row->width(4)->number('youth_members', 'Youth (18-35)');
            $row->width(4)->number('pwd_members', 'Total PWD');
        });
        
        $form->divider('Facilitation & Contact');
        
        // Facilitator
        $form->row(function ($row) {
            $row->width(4)->text('contact_person_name', 'Facilitator/Contact Name');
            $row->width(4)->text('contact_person_phone', 'Phone Number');
            $row->width(4)->select('facilitator_sex', 'Facilitator Gender')->options([
                'Male' => 'Male',
                'Female' => 'Female',
            ]);
        });
        
        $form->row(function ($row) {
            $row->width(6)->select('facilitator_id', 'Linked User Account')->options(
                User::pluck('name', 'id')
            )->help('Optional: Link to system user');
            $row->width(6)->date('registration_date', 'System Registration Date')->default(date('Y-m-d'));
        });
        
        // Meeting schedule (optional)
        $form->row(function ($row) {
            $row->width(6)->select('meeting_day', 'Meeting Day')->options([
                'Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday',
                'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday', 'Sunday' => 'Sunday'
            ]);
            $row->width(6)->select('meeting_frequency', 'Frequency')->options(FfsGroup::getMeetingFrequencies())->default('Weekly');
        });
        
        // Note: Code is auto-generated by FfsGroup model boot() method
        
        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
