<?php

namespace App\Admin\Controllers;

use App\Models\FfsGroup;
use App\Models\Location;
use App\Models\User;
use App\Models\Project;
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

        // Eager-load relationships + real member count to avoid N+1
        $grid->model()
            ->withCount('members')
            ->with(['implementingPartner', 'district', 'facilitator'])
            ->orderBy('id', 'desc');

        if ($groupType) {
            $grid->model()->where('type', $groupType);
        }

        // ── IP Scoping ──
        $this->applyIpScope($grid);

        // ── Field Facilitator Scoping ──
        $adminUser = \Encore\Admin\Facades\Admin::user();
        if ($adminUser && $adminUser->isRole('field_facilitator')) {
            $grid->model()->where('facilitator_id', $adminUser->id);
        }

        $grid->disableBatchActions();

        // ── Quick Search ──
        $grid->quickSearch(function ($model, $query) {
            $model->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('ip_name', 'like', "%{$query}%")
                  ->orWhere('district_text', 'like', "%{$query}%")
                  ->orWhere('primary_value_chain', 'like', "%{$query}%");
            });
        })->placeholder('Search by name, code, IP, district or activity...');

        // ── Filters ──
        $ipId = $this->getAdminIpId();
        $isSuperAdmin = $this->isSuperAdmin();

        $grid->filter(function ($filter) use ($ipId, $isSuperAdmin, $groupType) {
            $filter->disableIdFilter();

            $filter->like('name', 'Group Name');

            if (!$groupType) {
                $filter->equal('type', 'Group Type')->select(FfsGroup::getTypes());
            }

            $filter->equal('status', 'Status')->select(FfsGroup::getStatuses());

            if ($isSuperAdmin) {
                $filter->equal('ip_id', 'Implementing Partner')
                    ->select(ImplementingPartner::getDropdownOptions());
            }

            $filter->equal('district_id', 'District')->select(
                Location::where('type', 'District')->orderBy('name')->pluck('name', 'id')
            );

            $filter->like('subcounty_text', 'Subcounty');

            // Facilitator dropdown — only show users assigned as facilitators, with phone
            $facilitatorIds = FfsGroup::whereNotNull('facilitator_id')
                ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->distinct()->pluck('facilitator_id');
            $filter->equal('facilitator_id', 'Facilitator')->select(
                User::whereIn('id', $facilitatorIds)->orderBy('name')->get()
                    ->mapWithKeys(fn($u) => [
                        $u->id => $u->name . ($u->phone_number ? ' (' . $u->phone_number . ')' : ''),
                    ])
            );

            $filter->like('primary_value_chain', 'Value Chain');

            $filter->between('establishment_date', 'Established')->date();
        });

        // ── Columns ──

        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('name', 'Group Name')->display(function ($name) {
            $code = $this->code ? '<small class="text-muted">' . e($this->code) . '</small><br>' : '';
            return $code . '<strong>' . e($name) . '</strong>';
        })->sortable();

        if (!$groupType) {
            $grid->column('type', 'Type')->sortable();
        }

        $grid->column('status', 'Status')->display(function ($status) {
            $map = ['Active' => 'success', 'Inactive' => 'default', 'Suspended' => 'warning', 'Graduated' => 'info'];
            return '<span class="label label-' . ($map[$status] ?? 'default') . '">' . e($status) . '</span>';
        })->sortable();

        // IP — use eager-loaded relationship
        $grid->column('ip_id', 'IP')->display(function () {
            if ($this->implementingPartner) {
                return e($this->implementingPartner->short_name ?: $this->implementingPartner->name);
            }
            return $this->ip_name ?: '-';
        })->sortable();

        // District — use eager-loaded relationship
        $grid->column('district_id', 'District')->display(function () {
            if ($this->district) {
                return e($this->district->name);
            }
            return $this->district_text ?: '-';
        })->sortable('district_text');

        $grid->column('subcounty_text', 'Subcounty')->display(function ($v) {
            return $v ?: '-';
        })->sortable();

        // Real member count from withCount('members')
        $grid->column('members_count', 'Members')->sortable();

        $grid->column('primary_value_chain', 'Primary Activity')->display(function ($vc) {
            if (empty($vc)) return '-';
            return e(mb_strlen($vc) > 30 ? mb_substr($vc, 0, 27) . '...' : $vc);
        })->sortable()->hide();

        // Facilitator — use eager-loaded relationship, include phone
        $grid->column('facilitator_id', 'Facilitator')->display(function () {
            if ($this->facilitator) {
                $phone = $this->facilitator->phone_number
                    ? ' <small class="text-muted">(' . e($this->facilitator->phone_number) . ')</small>'
                    : '';
                return e($this->facilitator->name) . $phone;
            }
            return $this->contact_person_name ? e($this->contact_person_name) : '-';
        })->sortable();

        $grid->column('establishment_date', 'Established')->display(function ($d) {
            return $d ? date('Y', strtotime($d)) : '-';
        })->sortable();

        // Hidden columns available via column selector
        $grid->column('village', 'Village')->hide();
        $grid->column('parish_text', 'Parish')->hide();
        $grid->column('meeting_day', 'Meeting Day')->hide();
        $grid->column('meeting_frequency', 'Frequency')->hide();
        $grid->column('project_code', 'Project Code')->hide();
        $grid->column('source_file', 'Source')->hide();
        $grid->column('registration_date', 'Registered')->display(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '-';
        })->sortable()->hide();
        $grid->column('created_at', 'Created')->display(function ($d) {
            return date('d M Y', strtotime($d));
        })->sortable()->hide();

        return $grid;
    }

    /**
     * Inject a visible section heading into a Show panel.
     * divider() takes no args in Encore Admin — labels are ignored — so we
     * render the heading as an unescape HTML field with an empty label.
     */
    private function showSection(Show $show, string $label, string $icon = ''): void
    {
        $show->divider();
        $ico  = $icon ? "<i class='fa fa-{$icon} fa-fw'></i> " : '';
        $html = "<div style='margin:4px 0 2px;padding-bottom:4px;border-bottom:1px solid #ddd;'>"
              . "<span style='font-size:11px;font-weight:700;text-transform:uppercase;"
              . "letter-spacing:.6px;color:#666;'>{$ico}" . htmlspecialchars($label, ENT_QUOTES) . "</span></div>";
        static $idx = 0;
        $show->field('_section_' . (++$idx), '')->as(fn() => $html)->unescape();
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $record = FfsGroup::with([
            'implementingPartner', 'district', 'facilitator',
            'admin', 'secretary', 'treasurer', 'createdBy',
        ])->findOrFail($id);

        if (!$this->verifyIpAccess($record)) {
            return $this->denyIpAccess();
        }

        $realCount = $record->members()->count();

        $show = new Show($record);
        $show->panel()->style('primary')
            ->title($record->name . ($record->code ? ' — ' . $record->code : ''));

        // ── Basic Information ──
        $show->field('name', 'Group Name');
        $show->field('type', 'Group Type')->using(FfsGroup::getTypes());
        $show->field('status', 'Status')->as(function ($v) {
            $map = ['Active' => 'success', 'Inactive' => 'default', 'Suspended' => 'warning', 'Graduated' => 'info'];
            return '<span class="label label-' . ($map[$v] ?? 'default') . '">' . e($v ?: '-') . '</span>';
        })->unescape();
        $show->field('establishment_date', 'Established')->as(fn($d) => $d ? $d->format('d M Y') : '-');
        $show->field('registration_date', 'Registration Date')->as(fn($d) => $d ? $d->format('d M Y') : '-');

        // ── Partner / Project ──
        $this->showSection($show, 'Partner & Project', 'building-o');
        $show->field('ip_id', 'Implementing Partner')->as(function () {
            if (!$this->implementingPartner) {
                return $this->ip_name ? e($this->ip_name) : '-';
            }
            $ip  = $this->implementingPartner;
            $url = admin_url("implementing-partners/{$ip->id}");
            return "<a href='{$url}' style='color:#337ab7;'>"
                . "<i class='fa fa-external-link fa-fw'></i> " . e($ip->name) . "</a>";
        })->unescape();
        $show->field('project_code', 'Project Code')->as(fn($v) => $v ?: '-');
        $show->field('loa', 'Letter of Agreement')->as(fn($v) => $v ?: '-');
        $show->field('source_file', 'Import Source')->as(fn($v) => $v ?: '-');

        // ── Location ──
        $this->showSection($show, 'Location', 'map-marker');
        $show->field('district_id', 'District')->as(function () {
            if ($this->district) return e($this->district->name);
            return $this->district_text ? ucwords(strtolower($this->district_text)) : '-';
        });
        $show->field('subcounty_text', 'Subcounty')->as(fn($v) => $v ? ucwords(strtolower($v)) : '-');
        $show->field('parish_text', 'Parish')->as(fn($v) => $v ? ucwords(strtolower($v)) : '-');
        $show->field('village', 'Village')->as(fn($v) => $v ? ucwords(strtolower($v)) : '-');
        $show->field('latitude', 'GPS (Lat, Long)')->as(function ($lat) {
            $lng = $this->longitude;
            return ($lat && $lng) ? "{$lat}, {$lng}" : '-';
        });

        // ── Meeting Schedule ──
        $this->showSection($show, 'Meeting Schedule', 'calendar');
        $show->field('meeting_venue', 'Meeting Venue')->as(fn($v) => $v ?: '-');
        $show->field('meeting_day', 'Meeting Day')->as(fn($v) => $v ?: '-');
        $show->field('meeting_frequency', 'Meeting Frequency')->as(fn($v) => $v ?: '-');

        // ── Activities / Value Chains ──
        $this->showSection($show, 'Value Chains / Activities', 'leaf');
        $show->field('primary_value_chain', 'Primary Activity')->as(fn($v) => $v ?: '-');
        $show->field('secondary_value_chains', 'Other Activities')->as(function ($v) {
            if (empty($v)) return '-';
            $arr = is_string($v) ? json_decode($v, true) : $v;
            return is_array($arr) && count($arr) ? implode(', ', array_filter($arr)) : '-';
        });

        // ── Membership ──
        $this->showSection($show, 'Membership', 'users');
        $show->field('id', 'Members in System')->as(function () use ($realCount) {
            return "<strong style='font-size:18px;color:#05179F;'>{$realCount}</strong>";
        })->unescape();
        $show->field('male_members', 'Male (Reported)')->as(fn($v) => (int)($v ?? 0));
        $show->field('female_members', 'Female (Reported)')->as(fn($v) => (int)($v ?? 0));
        $show->field('youth_members', 'Youth 18–35')->as(fn($v) => (int)($v ?? 0) ?: '-');
        $show->field('pwd_male_members', 'PWD')->as(function () {
            $m = (int)($this->pwd_male_members ?? 0);
            $f = (int)($this->pwd_female_members ?? 0);
            return ($m + $f) ? "{$m}M / {$f}F" : '-';
        });

        // ── Facilitation ──
        $this->showSection($show, 'Facilitation & Contact', 'user-circle-o');
        $show->field('facilitator_id', 'Facilitator')->as(function () {
            if (!$this->facilitator) {
                return $this->contact_person_name ?: '-';
            }
            $url   = admin_url("facilitators/{$this->facilitator_id}");
            $phone = $this->facilitator->phone_number
                ? ' <span class="text-muted">&bull; ' . e($this->facilitator->phone_number) . '</span>' : '';
            return "<a href='{$url}' style='color:#337ab7;'>"
                . "<i class='fa fa-user fa-fw'></i> " . e($this->facilitator->name) . "</a>" . $phone;
        })->unescape();
        $show->field('facilitator_sex', 'Facilitator Gender')->as(fn($v) => $v ?: '-');
        $show->field('contact_person_phone', 'Contact Phone')->as(function ($v) {
            if ($v) return $v;
            return ($this->facilitator && $this->facilitator->phone_number)
                ? $this->facilitator->phone_number : '-';
        });

        // ── Group Officers ──
        $this->showSection($show, 'Group Officers', 'id-card-o');
        $show->field('admin_id', 'Chairperson')->as(function () {
            if (!$this->admin) return '-';
            $url   = admin_url("ffs-members/{$this->admin_id}");
            $phone = $this->admin->phone_number
                ? ' <span class="text-muted">&bull; ' . e($this->admin->phone_number) . '</span>' : '';
            return "<a href='{$url}' style='color:#337ab7;'>"
                . "<i class='fa fa-user fa-fw'></i> " . e($this->admin->name) . "</a>" . $phone;
        })->unescape();
        $show->field('secretary_id', 'Secretary')->as(function () {
            if (!$this->secretary) return '-';
            $url   = admin_url("ffs-members/{$this->secretary_id}");
            $phone = $this->secretary->phone_number
                ? ' <span class="text-muted">&bull; ' . e($this->secretary->phone_number) . '</span>' : '';
            return "<a href='{$url}' style='color:#337ab7;'>"
                . "<i class='fa fa-user fa-fw'></i> " . e($this->secretary->name) . "</a>" . $phone;
        })->unescape();
        $show->field('treasurer_id', 'Treasurer')->as(function () {
            if (!$this->treasurer) return '-';
            $url   = admin_url("ffs-members/{$this->treasurer_id}");
            $phone = $this->treasurer->phone_number
                ? ' <span class="text-muted">&bull; ' . e($this->treasurer->phone_number) . '</span>' : '';
            return "<a href='{$url}' style='color:#337ab7;'>"
                . "<i class='fa fa-user fa-fw'></i> " . e($this->treasurer->name) . "</a>" . $phone;
        })->unescape();

        // ── Cycle Information ──
        $this->showSection($show, 'Cycle Information', 'refresh');
        $show->field('cycle_number', 'Cycle Number')->as(fn($v) => $v ?: '-');
        $show->field('cycle_start_date', 'Cycle Start')->as(fn($d) => $d ? $d->format('d M Y') : '-');
        $show->field('cycle_end_date', 'Cycle End')->as(fn($d) => $d ? $d->format('d M Y') : '-');

        // ── Notes ──
        $this->showSection($show, 'Additional Information', 'info-circle');
        $show->field('description', 'Description')->as(fn($v) => $v ?: '-');
        $show->field('objectives', 'Objectives')->as(fn($v) => $v ?: '-');
        $show->field('achievements', 'Achievements')->as(fn($v) => $v ?: '-');
        $show->field('challenges', 'Challenges')->as(fn($v) => $v ?: '-');
        $show->field('photo', 'Photo')->image();

        // ── Audit ──
        $this->showSection($show, 'Audit', 'clock-o');
        $show->field('created_by_id', 'Profiled By')->as(function () {
            return $this->createdBy ? e($this->createdBy->name) : 'System';
        });
        $show->field('created_at', 'Created')->as(fn($d) => $d ? date('d M Y H:i', strtotime($d)) : '-');
        $show->field('updated_at', 'Last Updated')->as(fn($d) => $d ? date('d M Y H:i', strtotime($d)) : '-');
        $show->field('original_id', 'Import Ref.')->as(fn($v) => $v ?: '-');

        // ── Group Members relation grid ──
        $show->relation('members', "Group Members ({$realCount})", function ($grid) {
            $grid->model()->orderBy('name');
            $grid->column('member_code', 'Code')->sortable()->display(fn($v) => $v ?: '-');
            $grid->column('name', 'Full Name')->sortable()->display(function ($name) {
                $url = admin_url("ffs-members/{$this->id}");
                return "<a href='{$url}' style='color:#337ab7;font-weight:500;'>"
                    . "<i class='fa fa-user fa-fw'></i> " . e($name) . "</a>";
            });
            $grid->column('phone_number', 'Phone')->display(fn($v) => $v ?: '-');
            $grid->column('sex', 'Gender')->display(function ($v) {
                if ($v === 'Male')   return '<span class="label label-info">M</span>';
                if ($v === 'Female') return '<span class="label label-danger">F</span>';
                return '-';
            });
            $grid->column('is_group_admin', 'Role')->display(function () {
                if ($this->is_group_admin === 'Yes')    return '<span class="label label-primary">Chairperson</span>';
                if ($this->is_group_secretary === 'Yes') return '<span class="label label-info">Secretary</span>';
                if ($this->is_group_treasurer === 'Yes') return '<span class="label label-warning">Treasurer</span>';
                return '<span class="label label-default">Member</span>';
            });
            $grid->column('national_id_number', 'NIN')->display(fn($v) => $v ?: '-');
            $grid->column('created_at', 'Joined')->display(fn($d) => $d ? date('d M Y', strtotime($d)) : '-')->sortable();

            $grid->disableCreateButton();
            $grid->disableExport();
            $grid->disableFilter();
            $grid->disableRowSelector();
            $grid->disableActions();
            $grid->paginate(20);
        });

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

        // Auto-assign IP for IP-admins; Super admins pick an IP
        $this->addIpFieldToForm($form);

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
        
        // Project Info (ip_id is already handled by addIpFieldToForm above)
        $form->row(function ($row) {
            $row->width(6)->text('project_code', 'Project Code')->placeholder('e.g. UNJP/UGA/068/EC');
            $row->width(6)->date('establishment_date', 'Establishment Date')->default(date('Y-m-d'));
        });
        
        $form->divider('Location');
        
        // Location
        $form->row(function ($row) {
            $row->width(4)->select('district_id', 'District')->options(
                Location::where('type', 'District')->pluck('name', 'id')
            );
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
            $row->width(4)->number('total_members', 'Total Members')->default(0)->help('Auto-calculated from Male + Female');
            $row->width(4)->number('youth_members', 'Youth (18-35)')->default(0);
            $row->width(4)->number('pwd_members', 'Total PWD')->default(0)->help('Auto-calculated from PWD Males + Females');
        });
        
        $form->divider('Facilitation & Contact');

        // Facilitator — dropdown of system users, defaults to current admin
        $form->row(function ($row) {
            $adminUser = \Encore\Admin\Facades\Admin::user();
            $ipId = $this->getAdminIpId();

            $usersQuery = User::orderBy('name');
            if ($ipId !== null) {
                $usersQuery->where('ip_id', $ipId);
            }
            $userOptions = $usersQuery->get()->mapWithKeys(
                fn($u) => [$u->id => $u->name . ($u->phone_number ? ' (' . $u->phone_number . ')' : '')]
            );

            $row->width(6)->select('facilitator_id', 'Facilitator')
                ->options($userOptions)
                ->default($adminUser ? $adminUser->id : null)
                ->help('Defaults to the user creating/profiling this group');
            $row->width(6)->date('registration_date', 'System Registration Date')->default(date('Y-m-d'));
        });

        // Meeting schedule (optional)
        $form->row(function ($row) {
            $row->width(6)->select('meeting_day', 'Meeting Day')->options([
                'Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday',
                'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday', 'Sunday' => 'Sunday'
            ])->default('Monday');
            $row->width(6)->select('meeting_frequency', 'Frequency')->options(FfsGroup::getMeetingFrequencies())->default('Weekly');
        });

        $form->divider('Partner & Project Info');
        $form->row(function ($row) {
            $row->width(12)->text('loa', 'Letter of Agreement (LoA)')->placeholder('e.g. LOA-2025-001');
        });

        // Note: Code is auto-generated by FfsGroup model boot() method

        // Auto-compute total_members and pwd_members from breakdown fields
        $form->saving(function (Form $form) {
            $male   = (int)($form->male_members ?? 0);
            $female = (int)($form->female_members ?? 0);
            $pwdM   = (int)($form->pwd_male_members ?? 0);
            $pwdF   = (int)($form->pwd_female_members ?? 0);

            // Auto-compute totals if breakdown adds up
            $computed = $male + $female;
            if ($computed > 0) {
                $form->total_members = $computed;
            }
            $form->pwd_members = $pwdM + $pwdF;

            // Auto-populate facilitator details from selected user
            if (!empty($form->facilitator_id)) {
                $facilitator = User::find($form->facilitator_id);
                if ($facilitator) {
                    // Always sync contact name & phone from facilitator
                    $form->contact_person_name = $facilitator->name;
                    if ($facilitator->phone_number) {
                        $form->contact_person_phone = $facilitator->phone_number;
                    }
                    if ($facilitator->sex) {
                        $form->facilitator_sex = $facilitator->sex;
                    }
                }
            }
        });

        // Auto-create default VSLA cycle when a new VSLA group is saved
        $form->saved(function (Form $form) {
            $group = $form->model();
            if ($group->type !== 'VSLA') {
                return;
            }

            // Only create if no cycle exists for this group yet
            $existingCycle = \App\Models\Project::where('group_id', $group->id)
                ->where('is_vsla_cycle', 'Yes')
                ->first();

            if ($existingCycle || !$form->isCreating()) {
                return;
            }

            $year = date('Y');
            $cycle = new \App\Models\Project();
            $cycle->is_vsla_cycle      = 'Yes';
            $cycle->is_active_cycle    = 'Yes';
            $cycle->group_id           = $group->id;
            $cycle->cycle_name         = "{$group->name} – Cycle 1 ({$year})";
            $cycle->title              = "{$group->name} Savings Cycle 1";
            $cycle->description        = "Default savings cycle automatically created for {$group->name}.";
            $cycle->status             = 'ongoing';
            $cycle->start_date         = date('Y-01-01');   // Jan 1 of current year
            $cycle->end_date           = date('Y-12-31');   // Dec 31 of current year
            $cycle->share_value        = 2000;               // Default UGX 2,000
            $cycle->meeting_frequency  = $group->meeting_frequency ?? 'Weekly';
            $cycle->loan_interest_rate = 10;                 // 10% default
            $cycle->interest_frequency = 'Monthly';
            $cycle->minimum_loan_amount   = 10000;
            $cycle->maximum_loan_multiple = 10;
            $cycle->late_payment_penalty  = 5;
            $cycle->created_by_id = \Encore\Admin\Facades\Admin::user()->id ?? null;
            $cycle->save();

            admin_toastr("VSLA group created. A default savings cycle for {$year} has been automatically created. You can edit it under Cycles.", 'info');
        });

        // Sync officer roles on User records when admins are assigned to group
        $form->saved(function (Form $form) {
            $group = $form->model();
            if (!$group) return;

            // Sync chairperson flag
            if ($group->admin_id) {
                User::where('id', $group->admin_id)->update([
                    'is_group_admin' => 'Yes',
                    'group_id' => $group->id,
                ]);
            }
            // Sync secretary flag
            if ($group->secretary_id) {
                User::where('id', $group->secretary_id)->update([
                    'is_group_secretary' => 'Yes',
                    'group_id' => $group->id,
                ]);
            }
            // Sync treasurer flag
            if ($group->treasurer_id) {
                User::where('id', $group->treasurer_id)->update([
                    'is_group_treasurer' => 'Yes',
                    'group_id' => $group->id,
                ]);
            }
        });

        $form->disableViewCheck();
        $form->disableCreatingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
