<?php

namespace App\Admin\Controllers;

use App\Models\AesaObservation;
use App\Models\AesaSession;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Facades\Admin;

/**
 * AESA Observations Admin Controller
 *
 * Lists, filters & displays individual animal observations.
 * No create/edit form — data entry happens exclusively on mobile.
 */
class AesaObservationController extends AdminController
{
    use IpScopeable;

    protected $title = 'Animal Observations';

    /**
     * Grid — list all AESA observations
     */
    protected function grid()
    {
        $grid = new Grid(new AesaObservation());
        $this->applyIpScope($grid);

        $grid->model()->with(['session'])->orderBy('id', 'desc');

        // Disable create/edit — mobile only
        $grid->disableCreateButton();
        $grid->disableBatchActions();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        // ── Columns ──────────────────────────────────────────

        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('session.data_sheet_number', 'Session')->display(function ($v) {
            if (!$v) return '—';
            $url = admin_url('aesa-admin-sessions/' . $this->aesa_session_id);
            return "<a href='{$url}' style='font-weight:600;'>{$v}</a>";
        })->sortable();

        $grid->column('animal_id_tag', 'Tag')->display(function ($v) {
            return $v ? "<strong>{$v}</strong>" : '<span style="color:#999;">—</span>';
        })->sortable();

        $grid->column('animal_type', 'Animal Type')->display(function ($v) {
            $icons = [
                'Cattle'  => '🐄',
                'Goat'    => '🐐',
                'Sheep'   => '🐑',
                'Poultry' => '🐔',
                'Pig'     => '🐷',
                'Donkey'  => '🫏',
            ];
            $icon = $icons[$v] ?? '🐾';
            return $v ? "{$icon} {$v}" : '—';
        })->sortable();

        $grid->column('breed', 'Breed')->sortable()->hide();
        $grid->column('sex', 'Sex')->sortable();
        $grid->column('age_category', 'Age')->sortable()->hide();

        $grid->column('animal_health_status', 'Health Status')->display(function ($v) {
            $colors = [
                'Healthy'         => '#4caf50',
                'Sick'            => '#f44336',
                'Under Treatment' => '#ff9800',
                'Recovering'      => '#2196f3',
            ];
            $color = $colors[$v] ?? '#999';
            return $v
                ? "<span style='display:inline-block;padding:2px 10px;background:{$color};color:#fff;font-size:11px;font-weight:600;'>{$v}</span>"
                : '—';
        })->sortable();

        $grid->column('body_condition', 'Body Condition')->display(function ($v) {
            $colors = [
                'Good'  => '#4caf50',
                'Fair'  => '#ff9800',
                'Poor'  => '#f44336',
            ];
            $color = $colors[$v] ?? '#999';
            return $v ? "<span style='color:{$color};font-weight:600;'>{$v}</span>" : '—';
        })->sortable();

        $grid->column('risk_level', 'Risk')->display(function ($v) {
            $colors = [
                'Low'      => '#4caf50',
                'Medium'   => '#ff9800',
                'High'     => '#f44336',
                'Critical' => '#b71c1c',
            ];
            $color = $colors[$v] ?? '#999';
            return $v
                ? "<span style='display:inline-block;padding:2px 10px;background:{$color};color:#fff;font-size:11px;font-weight:600;'>{$v}</span>"
                : '—';
        })->sortable();

        $grid->column('health_score', 'Score')->display(function () {
            $score = $this->health_score;
            if (!is_numeric($score)) return '—';
            $color = '#f44336';
            if ($score >= 80) $color = '#4caf50';
            elseif ($score >= 60) $color = '#ff9800';
            elseif ($score >= 40) $color = '#ff5722';
            return "<span style='color:{$color};font-weight:700;font-size:14px;'>{$score}</span>";
        })->sortable();

        $grid->column('main_problem', 'Main Problem')->display(function ($v) {
            return $v ?: '<span style="color:#999;">None</span>';
        })->hide();

        $grid->column('symptoms_summary', 'Symptoms')->display(function () {
            $symptoms = [];
            if ($this->wounds_injuries) $symptoms[] = 'Wounds';
            if ($this->skin_infection) $symptoms[] = 'Skin Inf.';
            if ($this->swelling) $symptoms[] = 'Swelling';
            if ($this->coughing) $symptoms[] = 'Coughing';
            if ($this->diarrhea) $symptoms[] = 'Diarrhea';
            if (empty($symptoms)) return '<span style="color:#4caf50;font-size:11px;">None</span>';
            return '<span style="color:#f44336;font-size:11px;">' . implode(', ', $symptoms) . '</span>';
        });

        $grid->column('created_at', 'Date')->display(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '—';
        })->sortable()->hide();

        // ── Filters ──────────────────────────────────────────

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->column(1 / 3, function ($filter) {
                $filter->like('animal_id_tag', 'Animal Tag');
                $filter->equal('animal_type', 'Animal Type')->select(
                    array_combine(AesaObservation::ANIMAL_TYPES, AesaObservation::ANIMAL_TYPES)
                );
            });

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('animal_health_status', 'Health Status')->select(
                    array_combine(AesaObservation::HEALTH_STATUSES, AesaObservation::HEALTH_STATUSES)
                );
                $filter->equal('risk_level', 'Risk Level')->select(
                    array_combine(AesaObservation::RISK_LEVELS, AesaObservation::RISK_LEVELS)
                );
            });

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('body_condition', 'Body Condition')->select(
                    array_combine(AesaObservation::BODY_CONDITIONS, AesaObservation::BODY_CONDITIONS)
                );
                $filter->equal('sex', 'Sex')->select(
                    array_combine(AesaObservation::SEXES, AesaObservation::SEXES)
                );
            });
        });

        // ── Export ───────────────────────────────────────────

        $grid->export(function ($export) {
            $export->filename('AESA_Observations_' . date('Y-m-d'));
        });

        return $grid;
    }

    /**
     * Detail — show a single observation with all 8 sections
     */
    protected function detail($id)
    {
        $obs = AesaObservation::with(['session'])->findOrFail($id);

        $show = new Show($obs);

        $sessionRef = $obs->session ? $obs->session->data_sheet_number : 'Unknown';
        $show->panel()->style('primary')->title("Observation — {$sessionRef}");

        // Section 2: Animal Identification
        $show->divider('Section 2 — Animal Identification');
        $show->field('animal_id_tag', 'ID Tag');
        $show->field('animal_type', 'Animal Type');
        $show->field('breed', 'Breed');
        $show->field('colour', 'Colour');
        $show->field('sex', 'Sex');
        $show->field('age_category', 'Age Category');
        $show->field('date_of_birth', 'Date of Birth')->as(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '—';
        });
        $show->field('weight_kg', 'Weight (kg)');
        $show->field('height_cm', 'Height (cm)');
        $show->field('owner_name', 'Owner');
        $show->field('animal_health_status', 'Health Status');

        // Section 3: Weather
        $show->divider('Section 3 — Weather & Environment');
        $show->field('weather_condition', 'Weather');
        $show->field('temperature_level', 'Temperature');
        $show->field('humidity_level', 'Humidity');
        $show->field('rainfall_occurrence', 'Rainfall')->as(function ($v) {
            return $v ? 'Yes' : 'No';
        });
        $show->field('wind_intensity', 'Wind');
        $show->field('additional_weather_notes', 'Weather Notes');

        // Section 4: Health & Physical
        $show->divider('Section 4 — Health & Physical Examination');
        $show->field('body_condition', 'Body Condition');
        $show->field('eyes_condition', 'Eyes');
        $show->field('coat_condition', 'Coat/Skin');
        $show->field('appetite', 'Appetite');
        $show->field('movement', 'Movement');
        $show->field('behaviour', 'Behaviour');

        // Parasites
        $show->divider('Parasite Levels');
        $show->field('ticks_level', 'Ticks');
        $show->field('fleas_level', 'Fleas');
        $show->field('lice_level', 'Lice');
        $show->field('mites_level', 'Mites');

        // Symptoms
        $show->divider('Clinical Symptoms');
        $show->field('wounds_injuries', 'Wounds/Injuries')->as(fn($v) => $v ? '✅ Yes' : '❌ No');
        $show->field('wounds_injuries_description', 'Wounds Description');
        $show->field('skin_infection', 'Skin Infection')->as(fn($v) => $v ? '✅ Yes' : '❌ No');
        $show->field('swelling', 'Swelling')->as(fn($v) => $v ? '✅ Yes' : '❌ No');
        $show->field('coughing', 'Coughing')->as(fn($v) => $v ? '✅ Yes' : '❌ No');
        $show->field('diarrhea', 'Diarrhea')->as(fn($v) => $v ? '✅ Yes' : '❌ No');
        $show->field('other_symptoms', 'Other Symptoms');

        // Section 5: Ecosystem
        $show->divider('Section 5 — Agro-Ecosystem');
        $show->field('feed_availability', 'Feed');
        $show->field('water_availability', 'Water');
        $show->field('grazing_condition', 'Grazing');
        $show->field('housing_condition', 'Housing');
        $show->field('hygiene_condition', 'Hygiene');
        $show->field('animal_interaction', 'Interactions');

        // Section 6: Problem Analysis
        $show->divider('Section 6 — Problem Identification');
        $show->field('main_problem', 'Main Problem');
        $show->field('cause_of_problem', 'Cause');
        $show->field('risk_level', 'Risk Level');
        $show->field('problem_description', 'Description');

        // Section 7: Action Plan
        $show->divider('Section 7 — Action Plan');
        $show->field('immediate_action', 'Immediate Action');
        $show->field('preventive_action', 'Preventive Action');
        $show->field('monitoring_plan', 'Monitoring Plan');
        $show->field('responsible_person', 'Responsible Person');
        $show->field('follow_up_date', 'Follow-up Date')->as(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '—';
        });

        // Section 8: Group Discussion
        $show->divider('Section 8 — Group Discussion');
        $show->field('mini_group_findings', 'Mini-Group Findings');
        $show->field('feedback_from_members', 'Member Feedback');
        $show->field('final_agreed_decision', 'Agreed Decision');
        $show->field('facilitator_remarks', 'Facilitator Remarks');

        // Health Score
        $show->divider('Computed Health Score');
        $show->field('health_score_display', 'Health Score')->unescape()->as(function () {
            $score = $this->health_score;
            if (!is_numeric($score)) return '—';
            $color = '#f44336';
            $label = 'Critical';
            if ($score >= 80) { $color = '#4caf50'; $label = 'Good'; }
            elseif ($score >= 60) { $color = '#ff9800'; $label = 'Fair'; }
            elseif ($score >= 40) { $color = '#ff5722'; $label = 'Poor'; }
            return "<div style='display:inline-flex;align-items:center;gap:10px;'>
                <span style='display:inline-block;width:50px;height:50px;background:{$color};color:#fff;text-align:center;line-height:50px;font-size:20px;font-weight:700;'>{$score}</span>
                <span style='font-weight:600;color:{$color};font-size:16px;'>{$label}</span>
            </div>";
        });

        return $show;
    }

    /**
     * Form — disabled (mobile-only data entry)
     */
    protected function form()
    {
        $form = new Form(new AesaObservation());
        $form->display('id', 'ID');
        Admin::script("toastr.info('AESA observations are created and edited on the mobile app only.');");
        return $form;
    }
}
