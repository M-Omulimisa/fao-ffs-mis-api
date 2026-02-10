<?php

namespace App\Admin\Controllers;

use App\Models\ImplementingPartner;
use App\Models\FfsGroup;
use App\Models\FfsTrainingSession;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;

/**
 * ImplementingPartnerController — CRUD for Implementing Partners (IPs).
 *
 * Each IP is a partner organisation whose users, groups, and training
 * sessions are scoped to it for multi-tenancy data isolation.
 */
class ImplementingPartnerController extends AdminController
{
    protected $title = 'Implementing Partners';

    // ─── Index with stats header ─────────────────────────
    public function index(Content $content)
    {
        return $content
            ->title($this->title())
            ->description('Manage partner organisations')
            ->row(function (Row $row) {
                $this->addStatsRow($row);
            })
            ->body($this->grid());
    }

    private function addStatsRow(Row $row)
    {
        $total    = ImplementingPartner::count();
        $active   = ImplementingPartner::active()->count();
        $inactive = ImplementingPartner::where('status', 'inactive')->count();

        $row->column(12, function (Column $column) use ($total, $active, $inactive) {
            $html = "<div style='display:flex;gap:12px;margin-bottom:16px;'>";

            $cards = [
                ['label' => 'Total IPs',    'count' => $total,    'icon' => 'fa-handshake', 'color' => '#05179F'],
                ['label' => 'Active',        'count' => $active,   'icon' => 'fa-check-circle', 'color' => '#4caf50'],
                ['label' => 'Inactive',      'count' => $inactive, 'icon' => 'fa-pause-circle', 'color' => '#ff9800'],
            ];

            foreach ($cards as $c) {
                $html .= "
                    <div style='flex:1;background:#fff;border:1px solid #ddd;padding:14px;text-align:center;'>
                        <i class='fa {$c['icon']}' style='font-size:20px;color:{$c['color']};'></i>
                        <div style='font-size:28px;font-weight:700;margin:4px 0;color:{$c['color']};'>{$c['count']}</div>
                        <div style='font-size:11px;text-transform:uppercase;font-weight:600;color:#666;'>{$c['label']}</div>
                    </div>";
            }
            $html .= "</div>";
            $column->append($html);
        });
    }

    // ─── Grid ────────────────────────────────────────────
    protected function grid()
    {
        $grid = new Grid(new ImplementingPartner());
        $grid->model()->orderBy('name');
        $grid->quickSearch('name', 'short_name', 'slug')->placeholder('Search IPs…');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Name');
            $filter->equal('status', 'Status')->select(ImplementingPartner::getStatuses());
            $filter->like('region', 'Region');
        });

        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('logo', 'Logo')->image('', 36, 36)->width(50);

        $grid->column('name', 'Name')->display(function ($name) {
            $short = $this->short_name ? " <span class='text-muted'>({$this->short_name})</span>" : '';
            return "<strong>{$name}</strong>{$short}";
        })->sortable();

        $grid->column('region', 'Region')->label('primary')->sortable();

        // Live counts
        $grid->column('users_count', 'Users')->display(function () {
            return User::where('ip_id', $this->id)->count();
        })->label('info');

        $grid->column('groups_count', 'Groups')->display(function () {
            return FfsGroup::where('ip_id', $this->id)->count();
        })->label('info');

        $grid->column('sessions_count', 'Sessions')->display(function () {
            return FfsTrainingSession::where('ip_id', $this->id)->count();
        })->label('default');

        $grid->column('contact_person', 'Contact')->limit(25);

        $grid->column('status', 'Status')->display(function ($status) {
            $colors = ['active' => 'success', 'inactive' => 'warning', 'suspended' => 'danger'];
            $label  = ImplementingPartner::getStatuses()[$status] ?? $status;
            $color  = $colors[$status] ?? 'default';
            return "<span class='label label-{$color}'>{$label}</span>";
        })->sortable();

        $grid->column('created_at', 'Created')
            ->display(fn ($d) => $d ? date('M d, Y', strtotime($d)) : '-')
            ->sortable();

        return $grid;
    }

    // ─── Detail ──────────────────────────────────────────
    protected function detail($id)
    {
        $show = new Show(ImplementingPartner::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('name', 'Name');
        $show->field('short_name', 'Short Name');
        $show->field('slug', 'Slug');
        $show->field('description', 'Description');
        $show->field('logo', 'Logo')->image();
        $show->divider();
        $show->field('loa', 'Letter of Agreement');
        $show->field('project_code', 'Project Code');
        $show->divider();
        $show->field('contact_person', 'Contact Person');
        $show->field('contact_email', 'Contact Email');
        $show->field('contact_phone', 'Contact Phone');
        $show->field('address', 'Address');
        $show->divider();
        $show->field('region', 'Region');
        $show->field('districts', 'Districts')->as(function ($districts) {
            return is_array($districts) ? implode(', ', $districts) : $districts;
        });
        $show->field('status', 'Status');
        $show->field('start_date', 'Start Date');
        $show->field('end_date', 'End Date');
        $show->field('created_at', 'Created');
        $show->field('updated_at', 'Updated');

        return $show;
    }

    // ─── Form ────────────────────────────────────────────
    protected function form()
    {
        $form = new Form(new ImplementingPartner());

        $form->tab('Basic Info', function (Form $form) {
            $form->text('name', 'Partner Name')->rules('required|max:150');
            $form->text('short_name', 'Short Name / Code')->placeholder('e.g. KADP, ECO, GARD');
            $form->text('slug', 'Slug')->help('Auto-generated from name if blank');
            $form->textarea('description', 'Description');
            $form->image('logo', 'Logo');
            $form->select('status', 'Status')
                ->options(ImplementingPartner::getStatuses())
                ->default('active');
        });

        $form->tab('Agreement & Project', function (Form $form) {
            $form->text('loa', 'Letter of Agreement');
            $form->text('project_code', 'Project Code')->placeholder('e.g. UNJP/UGA/068/EC');
            $form->date('start_date', 'Start Date');
            $form->date('end_date', 'End Date');
        });

        $form->tab('Contact', function (Form $form) {
            $form->text('contact_person', 'Contact Person');
            $form->email('contact_email', 'Contact Email');
            $form->text('contact_phone', 'Contact Phone');
            $form->textarea('address', 'Address');
        });

        $form->tab('Coverage', function (Form $form) {
            $form->text('region', 'Region')->placeholder('e.g. Karamoja');
            $form->tags('districts', 'Districts')->help('Add district names covered by this IP');
        });

        return $form;
    }
}
