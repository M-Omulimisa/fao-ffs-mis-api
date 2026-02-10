<?php

namespace App\Admin\Controllers;

use App\Models\SeriesMovie;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;

/**
 * SeriesMovieController — Single controller, multiple slug-based routes.
 *
 * Routes that ALL point here:
 *   series-movies           →  shows ALL records
 *   series-movies-pending   →  auto-filters fix_status = pending
 *   series-movies-success   →  auto-filters fix_status = success
 *   series-movies-fail      →  auto-filters fix_status = failed
 */
class SeriesMovieController extends AdminController
{
    protected $title = 'Series Movies';

    // ─── Slug Detection ──────────────────────────────────
    private function detectFixFilter(): ?string
    {
        $url = url()->current();

        if (strpos($url, 'series-movies-fail') !== false) {
            return SeriesMovie::FIX_FAILED;
        }
        if (strpos($url, 'series-movies-success') !== false) {
            return SeriesMovie::FIX_SUCCESS;
        }
        if (strpos($url, 'series-movies-pending') !== false) {
            return SeriesMovie::FIX_PENDING;
        }

        return null; // "series-movies" → show all
    }

    protected function title()
    {
        $filter = $this->detectFixFilter();

        return match ($filter) {
            SeriesMovie::FIX_PENDING => '🟡 Series Movies — Pending Fix',
            SeriesMovie::FIX_SUCCESS => '🟢 Series Movies — Fixed (Success)',
            SeriesMovie::FIX_FAILED  => '🔴 Series Movies — Failed Fix',
            default                  => '🎬 Series Movies — All',
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
        $total   = SeriesMovie::count();
        $pending = SeriesMovie::fixPending()->count();
        $success = SeriesMovie::fixSuccess()->count();
        $failed  = SeriesMovie::fixFailed()->count();

        $activeFilter = $this->detectFixFilter();

        $cards = [
            ['label' => 'Total',   'count' => $total,   'icon' => 'fa-film',              'color' => '#05179F', 'slug' => 'series-movies',         'filter' => null],
            ['label' => 'Pending', 'count' => $pending, 'icon' => 'fa-clock',             'color' => '#ff9800', 'slug' => 'series-movies-pending', 'filter' => SeriesMovie::FIX_PENDING],
            ['label' => 'Success', 'count' => $success, 'icon' => 'fa-check-circle',      'color' => '#4caf50', 'slug' => 'series-movies-success', 'filter' => SeriesMovie::FIX_SUCCESS],
            ['label' => 'Failed',  'count' => $failed,  'icon' => 'fa-exclamation-circle', 'color' => '#f44336', 'slug' => 'series-movies-fail',   'filter' => SeriesMovie::FIX_FAILED],
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
        $grid = new Grid(new SeriesMovie());

        $fixFilter = $this->detectFixFilter();
        if ($fixFilter !== null) {
            $grid->model()->where('fix_status', $fixFilter);
        }

        $grid->model()->orderBy('id', 'desc');
        $grid->quickSearch('title', 'slug')->placeholder('Search by title or slug');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('title', 'Title');
            $filter->equal('fix_status', 'Fix Status')->select(SeriesMovie::getFixStatuses());
            $filter->equal('status', 'Status')->select(SeriesMovie::getStatuses());
            $filter->equal('quality', 'Quality')->select(SeriesMovie::getQualities());
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

        $grid->column('seasons_count', 'Seasons')->display(function ($v) {
            return $v > 0 ? "<span class='label label-primary'>{$v}</span>" : '-';
        })->sortable();

        $grid->column('episodes_count', 'Episodes')->display(function ($v) {
            return $v > 0 ? "<span class='label label-default'>{$v}</span>" : '-';
        })->sortable();

        $grid->column('quality', 'Quality')->label('default')->sortable();

        $grid->column('status', 'Status')
            ->editable('select', SeriesMovie::getStatuses())
            ->sortable();

        $grid->column('fix_status', 'Fix Status')->display(function ($status) {
            $colors = ['pending' => 'warning', 'success' => 'success', 'failed' => 'danger'];
            $label  = SeriesMovie::getFixStatuses()[$status] ?? $status;
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
        $show = new Show(SeriesMovie::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('title', 'Title');
        $show->field('slug', 'Slug');
        $show->field('description', 'Description');
        $show->field('poster_image', 'Poster')->image();
        $show->field('category', 'Category');
        $show->field('genre', 'Genre');
        $show->field('year', 'Year');
        $show->field('rating', 'Rating');
        $show->field('seasons_count', 'Seasons');
        $show->field('episodes_count', 'Episodes');
        $show->field('source_url', 'Source URL')->link();
        $show->field('quality', 'Quality');
        $show->field('language', 'Language');
        $show->field('status', 'Status');
        $show->divider();
        $show->field('fix_status', 'Fix Status')->as(function ($status) {
            return SeriesMovie::getFixStatuses()[$status] ?? $status;
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
        $form = new Form(new SeriesMovie());

        $form->tab('Content', function (Form $form) {
            $form->text('title', 'Title')->rules('required');
            $form->text('slug', 'Slug')->help('Auto-generated from title if left blank');
            $form->textarea('description', 'Description');
            $form->image('poster_image', 'Poster Image');
            $form->text('category', 'Category');
            $form->text('genre', 'Genre');
            $form->number('year', 'Year')->default(date('Y'));
            $form->text('rating', 'Rating');
            $form->number('seasons_count', 'Seasons')->default(0);
            $form->number('episodes_count', 'Episodes')->default(0);
            $form->url('source_url', 'Source URL');
            $form->select('quality', 'Quality')->options(SeriesMovie::getQualities());
            $form->text('language', 'Language')->default('English');
            $form->select('status', 'Status')->options(SeriesMovie::getStatuses())->default('active');
        });

        $form->tab('Debug / Fix Status', function (Form $form) {
            $form->select('fix_status', 'Fix Status')
                ->options(SeriesMovie::getFixStatuses())
                ->default(SeriesMovie::FIX_PENDING)
                ->help('Set by admin after debugging');
            $form->textarea('fix_message', 'Fix Message')
                ->help('Reason for failure or notes about the fix');
            $form->datetime('last_fix_date', 'Last Fix Date')
                ->help('Auto-set when fix_status changes, or set manually');
        });

        // Auto-set last_fix_date when fix_status changes
        $form->saving(function (Form $form) {
            if ($form->fix_status && $form->model()->fix_status !== $form->fix_status) {
                $form->last_fix_date = now();
            }
        });

        return $form;
    }
}
