<?php

namespace App\Admin\Controllers;

use App\Models\Movie;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;

/**
 * MovieController — Single controller, multiple slug-based routes.
 *
 * Routes that ALL point here:
 *   movies           →  shows ALL records
 *   movies-pending   →  auto-filters fix_status = pending
 *   movies-success   →  auto-filters fix_status = success
 *   movies-fail      →  auto-filters fix_status = failed
 */
class MovieController extends AdminController
{
    protected $title = 'Movies';

    // ─── Slug Detection ──────────────────────────────────
    private function detectFixFilter(): ?string
    {
        $url = url()->current();

        if (strpos($url, 'movies-fail') !== false) {
            return Movie::FIX_FAILED;
        }
        if (strpos($url, 'movies-success') !== false) {
            return Movie::FIX_SUCCESS;
        }
        if (strpos($url, 'movies-pending') !== false) {
            return Movie::FIX_PENDING;
        }

        return null; // "movies" → show all
    }

    protected function title()
    {
        $filter = $this->detectFixFilter();

        return match ($filter) {
            Movie::FIX_PENDING => '🟡 Movies — Pending Fix',
            Movie::FIX_SUCCESS => '🟢 Movies — Fixed (Success)',
            Movie::FIX_FAILED  => '🔴 Movies — Failed Fix',
            default            => '🎥 Movies — All',
        };
    }

    // ─── Index with stats header ─────────────────────────
    public function index(Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['index'] ?? 'Debug & Content Management')
            ->row(function (Row $row) {
                $this->addStatsRow($row);
            })
            ->body($this->grid());
    }

    private function addStatsRow(Row $row)
    {
        $total   = Movie::count();
        $pending = Movie::fixPending()->count();
        $success = Movie::fixSuccess()->count();
        $failed  = Movie::fixFailed()->count();

        $activeFilter = $this->detectFixFilter();

        $cards = [
            ['label' => 'Total',   'count' => $total,   'icon' => 'fa-film',              'color' => '#05179F', 'slug' => 'movies',         'filter' => null],
            ['label' => 'Pending', 'count' => $pending, 'icon' => 'fa-clock',             'color' => '#ff9800', 'slug' => 'movies-pending', 'filter' => Movie::FIX_PENDING],
            ['label' => 'Success', 'count' => $success, 'icon' => 'fa-check-circle',      'color' => '#4caf50', 'slug' => 'movies-success', 'filter' => Movie::FIX_SUCCESS],
            ['label' => 'Failed',  'count' => $failed,  'icon' => 'fa-exclamation-circle', 'color' => '#f44336', 'slug' => 'movies-fail',   'filter' => Movie::FIX_FAILED],
        ];

        $row->column(12, function (Column $column) use ($cards, $activeFilter) {
            $html = "<div style='display:flex;gap:12px;margin-bottom:16px;'>";
            foreach ($cards as $c) {
                $isActive   = ($c['filter'] === $activeFilter);
                $border     = $isActive ? "border:2px solid {$c['color']};" : 'border:1px solid #ddd;';
                $bg         = $isActive ? "background:{$c['color']};color:#fff;" : 'background:#fff;color:#333;';
                $numColor   = $isActive ? 'color:#fff;' : "color:{$c['color']};";
                $labelColor = $isActive ? 'color:rgba(255,255,255,.85);' : 'color:#666;';
                $link       = admin_url($c['slug']);

                $html .= "
                    <a href='{$link}' style='flex:1;text-decoration:none;{$border}{$bg}padding:14px;text-align:center;transition:.2s;'>
                        <i class='fa {$c['icon']}' style='font-size:20px;{$numColor}'></i>
                        <div style='font-size:28px;font-weight:700;margin:4px 0;{$numColor}'>{$c['count']}</div>
                        <div style='font-size:11px;text-transform:uppercase;font-weight:600;{$labelColor}'>{$c['label']}</div>
                    </a>";
            }
            $html .= "</div>";
            $column->append($html);
        });
    }

    // ─── Grid ────────────────────────────────────────────
    protected function grid()
    {
        $grid = new Grid(new Movie());

        $fixFilter = $this->detectFixFilter();
        if ($fixFilter !== null) {
            $grid->model()->where('fix_status', $fixFilter);
        }

        $grid->model()->orderBy('id', 'desc');
        $grid->quickSearch('title', 'slug')->placeholder('Search by title or slug');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('title', 'Title');
            $filter->equal('fix_status', 'Fix Status')->select(Movie::getFixStatuses());
            $filter->equal('status', 'Status')->select(Movie::getStatuses());
            $filter->equal('quality', 'Quality')->select(Movie::getQualities());
            $filter->like('category', 'Category');
            $filter->between('year', 'Year');
            $filter->between('created_at', 'Created')->date();
        });

        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('poster_image', 'Poster')
            ->image('', 45, 65)
            ->width(60);

        $grid->column('title', 'Title')->display(function ($title) {
            return '<strong>' . \Illuminate\Support\Str::limit($title, 35) . '</strong>';
        })->sortable();

        $grid->column('category', 'Category')->label('info')->sortable();

        $grid->column('year', 'Year')->sortable();

        $grid->column('duration', 'Duration')->display(function () {
            return $this->duration_text;
        })->sortable();

        $grid->column('quality', 'Quality')->label('default')->sortable();

        $grid->column('status', 'Status')
            ->editable('select', Movie::getStatuses())
            ->sortable();

        $grid->column('fix_status', 'Fix Status')->display(function ($status) {
            $colors = ['pending' => 'warning', 'success' => 'success', 'failed' => 'danger'];
            $label  = Movie::getFixStatuses()[$status] ?? $status;
            $color  = $colors[$status] ?? 'default';
            return "<span class='label label-{$color}'>{$label}</span>";
        })->sortable();

        $grid->column('fix_message', 'Fix Message')->display(function ($msg) {
            return $msg ? '<span class="text-muted" title="' . e($msg) . '">' . \Illuminate\Support\Str::limit($msg, 30) . '</span>' : '-';
        });

        $grid->column('last_fix_date', 'Last Fix')->display(function ($date) {
            return $date ? \Carbon\Carbon::parse($date)->diffForHumans() : '-';
        })->sortable();

        $grid->column('created_at', 'Created')->display(function ($d) {
            return date('M d, Y', strtotime($d));
        })->sortable()->hide();

        return $grid;
    }

    // ─── Detail ──────────────────────────────────────────
    protected function detail($id)
    {
        $show = new Show(Movie::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('title', 'Title');
        $show->field('slug', 'Slug');
        $show->field('description', 'Description');
        $show->field('poster_image', 'Poster')->image();
        $show->field('category', 'Category');
        $show->field('genre', 'Genre');
        $show->field('year', 'Year');
        $show->field('rating', 'Rating');
        $show->field('duration', 'Duration (min)');
        $show->field('source_url', 'Source URL')->link();
        $show->field('quality', 'Quality');
        $show->field('language', 'Language');
        $show->field('status', 'Status');
        $show->divider();
        $show->field('fix_status', 'Fix Status')->as(function ($status) {
            return Movie::getFixStatuses()[$status] ?? $status;
        });
        $show->field('fix_message', 'Fix Message');
        $show->field('last_fix_date', 'Last Fix Date');
        $show->field('created_at', 'Created');
        $show->field('updated_at', 'Updated');

        return $show;
    }

    // ─── Form ────────────────────────────────────────────
    protected function form()
    {
        $form = new Form(new Movie());

        $form->tab('Content', function (Form $form) {
            $form->text('title', 'Title')->rules('required');
            $form->text('slug', 'Slug')->help('Auto-generated from title if left blank');
            $form->textarea('description', 'Description');
            $form->image('poster_image', 'Poster Image');
            $form->text('category', 'Category');
            $form->text('genre', 'Genre');
            $form->number('year', 'Year')->default(date('Y'));
            $form->text('rating', 'Rating');
            $form->number('duration', 'Duration (minutes)');
            $form->url('source_url', 'Source URL');
            $form->select('quality', 'Quality')->options(Movie::getQualities());
            $form->text('language', 'Language')->default('English');
            $form->select('status', 'Status')->options(Movie::getStatuses())->default('active');
        });

        $form->tab('Debug / Fix Status', function (Form $form) {
            $form->select('fix_status', 'Fix Status')
                ->options(Movie::getFixStatuses())
                ->default(Movie::FIX_PENDING)
                ->help('Set by admin after debugging');
            $form->textarea('fix_message', 'Fix Message')
                ->help('Reason for failure or notes about the fix');
            $form->datetime('last_fix_date', 'Last Fix Date')
                ->help('Auto-set when fix_status changes, or set manually');
        });

        $form->saving(function (Form $form) {
            if ($form->fix_status && $form->model()->fix_status !== $form->fix_status) {
                $form->last_fix_date = now();
            }
        });

        return $form;
    }
}
