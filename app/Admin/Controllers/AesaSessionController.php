<?php

namespace App\Admin\Controllers;

use App\Models\AesaSession;
use App\Models\AesaObservation;
use App\Models\AesaCropObservation;
use App\Models\FfsGroup;
use App\Models\User;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;

/**
 * AESA Sessions Admin Controller
 *
 * Lists, filters & displays AESA sessions & their observations.
 * No create/edit form — data entry happens exclusively on mobile.
 */
class AesaSessionController extends AdminController
{
    use IpScopeable;

    protected $title = 'AESA Sessions';

    /**
     * Grid — list all AESA sessions
     */
    protected function grid()
    {
        $grid = new Grid(new AesaSession());
        $this->applyIpScope($grid);

        $grid->model()->with(['group', 'facilitator'])->orderBy('id', 'desc');

        // Disable create/edit — mobile only
        $grid->disableCreateButton();
        $grid->disableBatchActions();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        // ── Columns ──────────────────────────────────────────

        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('data_sheet_number', 'Data Sheet #')->display(function ($v) {
            return "<strong>{$v}</strong>";
        })->sortable();

        $grid->column('group.name', 'FFS Group')->display(function ($name) {
            if ($name) return $name;
            return $this->group_name_other ?: '<span style="color:#999;">—</span>';
        })->sortable();

        $grid->column('district_text', 'District')->sortable()->hide();

        $grid->column('location_display', 'Location')->display(function () {
            $parts = array_filter([
                $this->village_text,
                $this->sub_county_text,
                $this->district_text,
            ]);
            return implode(', ', $parts) ?: '<span style="color:#999;">—</span>';
        });

        $grid->column('observation_date', 'Date')->display(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '—';
        })->sortable();

        $grid->column('observation_time', 'Time')->display(function ($t) {
            return $t ? date('H:i', strtotime($t)) : '—';
        })->hide();

        $grid->column('facilitator.first_name', 'Facilitator')->display(function () {
            if ($this->facilitator) {
                return trim(($this->facilitator->first_name ?? '') . ' ' . ($this->facilitator->last_name ?? ''));
            }
            return $this->facilitator_name ?: '<span style="color:#999;">—</span>';
        });

        $grid->column('mini_group_name', 'Mini-Group')->hide();
        $grid->column('observation_location', 'Location Type')->hide();

        $grid->column('observations_count', 'Animals')->display(function () {
            $count = $this->observations()->count();
            $color = $count > 0 ? '#05179F' : '#999';
            return "<span style='color:{$color};font-weight:700;'>{$count}</span>";
        });

        $grid->column('crop_observations_count', 'Crops')->display(function () {
            $count = $this->cropObservations()->count();
            $color = $count > 0 ? '#388e3c' : '#999';
            return "<span style='color:{$color};font-weight:700;'>{$count}</span>";
        });

        $grid->column('status', 'Status')->display(function ($status) {
            $colors = [
                'draft'     => '#ff9800',
                'submitted' => '#4caf50',
                'reviewed'  => '#2196f3',
            ];
            $color = $colors[$status] ?? '#999';
            $label = ucfirst($status);
            return "<span style='display:inline-block;padding:2px 10px;background:{$color};color:#fff;font-size:11px;font-weight:600;text-transform:uppercase;'>{$label}</span>";
        })->sortable();

        $grid->column('created_at', 'Created')->display(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '—';
        })->sortable()->hide();

        // ── Filters ──────────────────────────────────────────

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->column(1 / 3, function ($filter) {
                $filter->like('data_sheet_number', 'Data Sheet #');
                $filter->equal('status', 'Status')->select([
                    'draft'     => 'Draft',
                    'submitted' => 'Submitted',
                    'reviewed'  => 'Reviewed',
                ]);
            });

            $filter->column(1 / 3, function ($filter) {
                $ipId = $this->getAdminIpId();
                $groups = FfsGroup::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                    ->orderBy('name')->pluck('name', 'id')->toArray();
                $filter->equal('group_id', 'FFS Group')->select($groups);
                $filter->like('district_text', 'District');
            });

            $filter->column(1 / 3, function ($filter) {
                $filter->between('observation_date', 'Date Range')->date();
            });
        });

        // ── Export ───────────────────────────────────────────

        $grid->export(function ($export) {
            $export->filename('AESA_Sessions_' . date('Y-m-d'));
            $export->column('data_sheet_number', function ($value, $original) {
                return $original;
            });
        });

        return $grid;
    }

    /**
     * Detail — show a single session with all observations
     */
    protected function detail($id)
    {
        $session = AesaSession::with(['observations', 'cropObservations', 'group', 'facilitator'])->findOrFail($id);

        $animalCount = $session->observations->count();
        $cropCount   = $session->cropObservations->count();

        $show = new Show($session);
        $show->panel()->style('primary')->title(
            'AESA Session — ' . $session->data_sheet_number .
            '  (' . $animalCount . ' animal obs · ' . $cropCount . ' crop obs)'
        );

        // Session Identification
        $show->divider('Session Identification');
        $show->field('data_sheet_number', 'Data Sheet Number');
        $show->field('group.name', 'FFS Group');
        $show->field('group_name_other', 'Group (Other)');
        $show->field('district_text', 'District');
        $show->field('sub_county_text', 'Sub-County');
        $show->field('village_text', 'Village');
        $show->field('observation_date', 'Observation Date')->as(function ($d) {
            return $d ? date('l, d M Y', strtotime($d)) : '—';
        });
        $show->field('observation_time', 'Observation Time')->as(function ($t) {
            return $t ? date('h:i A', strtotime($t)) : '—';
        });
        $show->field('facilitator_name_display', 'Facilitator');
        $show->field('mini_group_name', 'Mini-Group');
        $show->field('observation_location', 'Observation Location');
        $show->field('observation_location_other', 'Location (Other)');

        // GPS
        $show->divider('GPS Coordinates');
        $show->field('gps_latitude', 'Latitude');
        $show->field('gps_longitude', 'Longitude');

        // Status & Audit
        $show->divider('Status & Audit');
        $show->field('status', 'Status')->as(function ($s) {
            return ucfirst($s);
        });
        $show->field('created_at', 'Created')->as(function ($d) {
            return $d ? date('d M Y h:i A', strtotime($d)) : '—';
        });
        $show->field('updated_at', 'Updated')->as(function ($d) {
            return $d ? date('d M Y h:i A', strtotime($d)) : '—';
        });

        // ── Animal Observations Table ────────────────────────

        $show->divider('🐄 Animal Observations (' . $animalCount . ')');

        $show->field('observations_html', ' ')->unescape()->as(function () use ($session) {
            $observations = $session->observations;
            if ($observations->isEmpty()) {
                return '<div style="padding:20px;text-align:center;color:#999;">No animal observations recorded for this session.</div>';
            }

            $rows = '';
            foreach ($observations as $i => $obs) {
                $num = $i + 1;
                $tag = $obs->animal_id_tag ?: '—';
                $type = $obs->animal_type ?: '—';
                $breed = $obs->breed ?: '—';
                $sex = $obs->sex ?: '—';
                $age = $obs->age_category ?: '—';
                $health = $obs->animal_health_status ?: '—';
                $body = $obs->body_condition ?: '—';
                $risk = $obs->risk_level ?: '—';
                $score = $obs->health_score ?? '—';

                $healthColor = '#999';
                if ($health === 'Healthy') $healthColor = '#4caf50';
                elseif ($health === 'Sick') $healthColor = '#f44336';
                elseif ($health === 'Under Treatment') $healthColor = '#ff9800';
                elseif ($health === 'Recovering') $healthColor = '#2196f3';

                $riskColor = '#999';
                if ($risk === 'Low') $riskColor = '#4caf50';
                elseif ($risk === 'Medium') $riskColor = '#ff9800';
                elseif ($risk === 'High') $riskColor = '#f44336';
                elseif ($risk === 'Critical') $riskColor = '#b71c1c';

                $scoreColor = '#f44336';
                if (is_numeric($score)) {
                    if ($score >= 80) $scoreColor = '#4caf50';
                    elseif ($score >= 60) $scoreColor = '#ff9800';
                    elseif ($score >= 40) $scoreColor = '#ff5722';
                }

                $viewUrl = admin_url('aesa-admin-observations/' . $obs->id);

                $rows .= "
                    <tr>
                        <td style='padding:8px;'>{$num}</td>
                        <td style='padding:8px;font-weight:600;'>{$tag}</td>
                        <td style='padding:8px;'>{$type}</td>
                        <td style='padding:8px;'>{$breed}</td>
                        <td style='padding:8px;'>{$sex}</td>
                        <td style='padding:8px;'>{$age}</td>
                        <td style='padding:8px;'><span style='color:{$healthColor};font-weight:600;'>{$health}</span></td>
                        <td style='padding:8px;'>{$body}</td>
                        <td style='padding:8px;'><span style='color:{$riskColor};font-weight:600;'>{$risk}</span></td>
                        <td style='padding:8px;'><span style='color:{$scoreColor};font-weight:700;'>{$score}</span></td>
                        <td style='padding:8px;'><a href='{$viewUrl}' class='btn btn-xs btn-primary' style='border-radius:0;'>View</a></td>
                    </tr>";
            }

            return "
                <div style='overflow-x:auto;'>
                    <table class='table table-bordered' style='margin:0;font-size:12px;'>
                        <thead style='background:#05179F;color:#fff;'>
                            <tr>
                                <th style='padding:8px;'>#</th>
                                <th style='padding:8px;'>Tag</th>
                                <th style='padding:8px;'>Type</th>
                                <th style='padding:8px;'>Breed</th>
                                <th style='padding:8px;'>Sex</th>
                                <th style='padding:8px;'>Age</th>
                                <th style='padding:8px;'>Health</th>
                                <th style='padding:8px;'>Body</th>
                                <th style='padding:8px;'>Risk</th>
                                <th style='padding:8px;'>Score</th>
                                <th style='padding:8px;'>Action</th>
                            </tr>
                        </thead>
                        <tbody>{$rows}</tbody>
                    </table>
                </div>";
        });

        // ── Crop Observations Table ──────────────────────────

        $show->divider('🌱 Crop Observations (' . $cropCount . ')');

        $show->field('crop_obs_html', ' ')->unescape()->as(function () use ($session) {
            $cropObs = $session->cropObservations;
            if ($cropObs->isEmpty()) {
                return '<div style="padding:20px;text-align:center;color:#999;">No crop observations recorded for this session.</div>';
            }

            $cropIcons = [
                'Maize' => '🌽', 'Sorghum' => '🌾', 'Millet' => '🌾',
                'Cassava' => '🥔', 'Beans' => '🫘', 'Groundnuts' => '🥜',
                'Sweet Potato' => '🍠',
            ];
            $vigorColors = [
                'Excellent' => '#1976d2', 'Good' => '#4caf50',
                'Moderate' => '#ff9800', 'Poor' => '#f44336',
            ];
            $riskColors = ['Low' => '#4caf50', 'Medium' => '#ff9800', 'High' => '#f44336'];
            $pressureColors = ['None' => '#4caf50', 'Low' => '#8bc34a', 'Medium' => '#ff9800', 'High' => '#f44336'];

            $rows = '';
            foreach ($cropObs as $i => $obs) {
                $num    = $i + 1;
                $icon   = $cropIcons[$obs->crop_type] ?? '🌱';
                $crop   = $icon . ' ' . ($obs->crop_type ?: '—');
                $plot   = $obs->plot_id ?: '—';
                $stage  = $obs->growth_stage ?: '—';
                $vigor  = $obs->crop_vigor ?: '—';
                $risk   = $obs->risk_level ?: '—';
                $score  = $obs->crop_health_score ?? '—';
                $pest   = $obs->pest_pressure_level ?: 'None';
                $disease = $obs->disease_pressure_level ?: 'None';

                $vigorColor   = $vigorColors[$vigor] ?? '#999';
                $riskColor    = $riskColors[$risk] ?? '#999';
                $pestColor    = $pressureColors[$pest] ?? '#999';
                $diseaseColor = $pressureColors[$disease] ?? '#999';

                $scoreColor = '#f44336';
                if (is_numeric($score)) {
                    if ($score >= 75) $scoreColor = '#4caf50';
                    elseif ($score >= 50) $scoreColor = '#ff9800';
                }

                $viewUrl = admin_url('aesa-admin-crop-observations/' . $obs->id);

                $rows .= "
                    <tr>
                        <td style='padding:8px;'>{$num}</td>
                        <td style='padding:8px;font-weight:600;'>{$plot}</td>
                        <td style='padding:8px;'>{$crop}</td>
                        <td style='padding:8px;color:#666;'>{$stage}</td>
                        <td style='padding:8px;'><span style='background:{$vigorColor};color:#fff;padding:2px 8px;font-size:11px;font-weight:600;'>{$vigor}</span></td>
                        <td style='padding:8px;'><span style='color:{$riskColor};font-weight:600;'>{$risk}</span></td>
                        <td style='padding:8px;'><span style='color:{$scoreColor};font-weight:700;'>{$score}/100</span></td>
                        <td style='padding:8px;'><span style='color:{$pestColor};font-weight:600;'>{$pest}</span></td>
                        <td style='padding:8px;'><span style='color:{$diseaseColor};font-weight:600;'>{$disease}</span></td>
                        <td style='padding:8px;'><a href='{$viewUrl}' class='btn btn-xs btn-success' style='border-radius:0;'>View</a></td>
                    </tr>";
            }

            return "
                <div style='overflow-x:auto;'>
                    <table class='table table-bordered' style='margin:0;font-size:12px;'>
                        <thead style='background:#388e3c;color:#fff;'>
                            <tr>
                                <th style='padding:8px;'>#</th>
                                <th style='padding:8px;'>Plot ID</th>
                                <th style='padding:8px;'>Crop</th>
                                <th style='padding:8px;'>Growth Stage</th>
                                <th style='padding:8px;'>Vigor</th>
                                <th style='padding:8px;'>Risk</th>
                                <th style='padding:8px;'>Score</th>
                                <th style='padding:8px;'>Pest Pressure</th>
                                <th style='padding:8px;'>Disease Pressure</th>
                                <th style='padding:8px;'>Action</th>
                            </tr>
                        </thead>
                        <tbody>{$rows}</tbody>
                    </table>
                </div>";
        });

        return $show;
    }

    /**
     * Form — disabled (mobile-only data entry)
     */
    protected function form()
    {
        $form = new Form(new AesaSession());
        $form->display('id', 'ID');
        $form->display('data_sheet_number', 'Data Sheet Number');
        Admin::script("toastr.info('AESA sessions are created and edited on the mobile app only.');");
        return $form;
    }
}
