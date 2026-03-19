<?php

namespace App\Admin\Controllers;

use App\Models\AesaCropObservation;
use App\Models\AesaSession;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Facades\Admin;

/**
 * AESA Crop Observations Admin Controller
 *
 * Lists, filters & displays individual crop observations.
 * Data entry happens exclusively on mobile — no create/edit form here.
 */
class AesaCropObservationController extends AdminController
{
    use IpScopeable;

    protected $title = 'Crop Observations';

    protected function grid()
    {
        $grid = new Grid(new AesaCropObservation());

        $grid->model()->with(['session'])->orderBy('id', 'desc');

        // Disable create/edit — mobile only
        $grid->disableCreateButton();
        $grid->disableBatchActions();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('session.data_sheet_number', 'Session')->display(function ($v) {
            if (!$v) return '—';
            $url = admin_url('aesa-admin-sessions/' . $this->aesa_session_id);
            return "<a href='{$url}' style='font-weight:600;'>{$v}</a>";
        })->sortable();

        $grid->column('plot_id', 'Plot ID')->display(function ($v) {
            return $v ? "<strong>{$v}</strong>" : '<span style="color:#999;">—</span>';
        })->sortable();

        $grid->column('crop_type', 'Crop')->display(function ($v) {
            $icons = [
                'Maize'       => '🌽',
                'Sorghum'     => '🌾',
                'Millet'      => '🌾',
                'Cassava'     => '🥔',
                'Beans'       => '🫘',
                'Groundnuts'  => '🥜',
                'Sweet Potato'=> '🍠',
            ];
            $icon = $icons[$v] ?? '🌱';
            return $v ? "{$icon} {$v}" : '—';
        })->sortable();

        $grid->column('variety', 'Variety')->sortable()->hide();
        $grid->column('growth_stage', 'Growth Stage')->sortable();

        $grid->column('crop_vigor', 'Vigor')->display(function ($v) {
            $colors = [
                'Excellent' => '#1976d2',
                'Good'      => '#4caf50',
                'Moderate'  => '#ff9800',
                'Poor'      => '#f44336',
            ];
            $color = $colors[$v] ?? '#999';
            return $v
                ? "<span style='display:inline-block;padding:2px 10px;background:{$color};color:#fff;font-size:11px;font-weight:600;'>{$v}</span>"
                : '—';
        })->sortable();

        $grid->column('risk_level', 'Risk')->display(function ($v) {
            $colors = [
                'Low'    => '#4caf50',
                'Medium' => '#ff9800',
                'High'   => '#f44336',
            ];
            $color = $colors[$v] ?? '#999';
            return $v
                ? "<span style='display:inline-block;padding:2px 10px;background:{$color};color:#fff;font-size:11px;font-weight:600;'>{$v}</span>"
                : '—';
        })->sortable();

        // Computed columns
        $grid->column('crop_health_score', 'Health Score')->display(function () {
            $score = $this->crop_health_score;
            $color = $score >= 75 ? '#4caf50' : ($score >= 50 ? '#ff9800' : '#f44336');
            return "<strong style='color:{$color};'>{$score}/100</strong>";
        });

        $grid->column('pest_pressure_level', 'Pest Pressure')->display(function () {
            $level = $this->pest_pressure_level;
            $colors = ['None' => '#4caf50', 'Low' => '#8bc34a', 'Medium' => '#ff9800', 'High' => '#f44336'];
            $color = $colors[$level] ?? '#999';
            return "<span style='color:{$color};font-weight:600;'>{$level}</span>";
        });

        $grid->column('disease_pressure_level', 'Disease Pressure')->display(function () {
            $level = $this->disease_pressure_level;
            $colors = ['None' => '#4caf50', 'Low' => '#8bc34a', 'Medium' => '#ff9800', 'High' => '#f44336'];
            $color = $colors[$level] ?? '#999';
            return "<span style='color:{$color};font-weight:600;'>{$level}</span>";
        });

        $grid->column('farmer_display', 'Farmer')->display(function () {
            return $this->farmer_display ?? '—';
        });

        $grid->column('created_at', 'Recorded')->display(function ($v) {
            return $v ? \Carbon\Carbon::parse($v)->format('d/m/Y H:i') : '—';
        })->sortable();

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $sessions = AesaSession::pluck('data_sheet_number', 'id')->toArray();
            $filter->equal('aesa_session_id', 'Session')->select($sessions);
            $filter->equal('crop_type', 'Crop Type')->select(
                array_combine(AesaCropObservation::CROP_TYPES, AesaCropObservation::CROP_TYPES)
            );
            $filter->equal('crop_vigor', 'Crop Vigor')->select(
                array_combine(AesaCropObservation::CROP_VIGORS, AesaCropObservation::CROP_VIGORS)
            );
            $filter->equal('risk_level', 'Risk Level')->select(
                array_combine(AesaCropObservation::RISK_LEVELS, AesaCropObservation::RISK_LEVELS)
            );
            $filter->equal('growth_stage', 'Growth Stage')->select(
                array_combine(AesaCropObservation::GROWTH_STAGES, AesaCropObservation::GROWTH_STAGES)
            );
        });

        $grid->export(function ($export) {
            $export->filename('AESA_Crop_Observations_' . date('Y-m-d'));
        });

        return $grid;
    }

    protected function detail($id)
    {
        $obs = AesaCropObservation::with(['session', 'farmer', 'createdBy'])->findOrFail($id);

        $show = new Show($obs);
        $show->panel()->style('success')
            ->title('🌿 Crop Observation — ' . ($obs->crop_type ?? 'Unknown Crop') . ' · Plot: ' . ($obs->plot_id ?? '—'));

        // Section 1: Session Reference
        $show->divider('📋 Session Reference');
        $show->field('session.data_sheet_number', 'Session Ref')->as(function ($v) use ($obs) {
            if (!$v) return '—';
            $url = admin_url('aesa-admin-sessions/' . $obs->aesa_session_id);
            return "<a href='{$url}' style='font-weight:600;'>{$v}</a>";
        })->unescape();

        // Section 2: Crop Identification
        $show->divider('🌱 Section 2 — Crop Identification');
        $show->field('plot_id', 'Plot ID');
        $show->field('farmer_name', 'Farmer Name');
        $show->field('crop_type_display', 'Crop Type');
        $show->field('variety', 'Variety');
        $show->field('cropping_system', 'Cropping System');
        $show->field('planting_method', 'Planting Method');
        $show->field('planting_date', 'Planting Date');
        $show->field('growth_stage', 'Growth Stage');
        $show->field('plot_size_acres', 'Plot Size (acres)');
        $show->field('irrigation_method', 'Irrigation');

        // Section 3: Weather Conditions
        $show->divider('🌤️ Section 3 — Weather Conditions');
        $show->field('weather_condition', 'Weather');
        $show->field('temperature_level', 'Temperature');
        $show->field('humidity_level', 'Humidity');
        $show->field('rainfall_occurrence', 'Rainfall Occurred')->as(fn($v) => $v ? '✅ Yes' : '❌ No');
        $show->field('wind_intensity', 'Wind Intensity');
        $show->field('additional_weather_notes', 'Weather Notes');

        // Section 4: Plant Health
        $show->divider('🌿 Section 4 — Plant Health & Growth');
        $show->field('population_density', 'Population Density');
        $show->field('plant_height_cm', 'Plant Height (cm)');
        $show->field('leaf_colour', 'Leaf Colour');
        $show->field('leaf_condition', 'Leaf Condition');
        $show->field('stem_condition', 'Stem Condition');
        $show->field('root_condition', 'Root Condition');
        $show->field('flowering_status', 'Flowering Status');
        $show->field('fruit_grain_formation', 'Fruit/Grain Formation');
        $show->field('crop_vigor', 'Crop Vigor');

        // Section 5: Pest Observation
        $show->divider('🐛 Section 5 — Pest Observation');
        $show->field('aphids_level', 'Aphids');
        $show->field('caterpillars_armyworms_level', 'Caterpillars / Armyworms');
        $show->field('beetles_level', 'Beetles');
        $show->field('grasshoppers_level', 'Grasshoppers');
        $show->field('whiteflies_level', 'Whiteflies');
        $show->field('other_insect_pests_text', 'Other Pests (Notes)');

        // Section 6: Disease Observation
        $show->divider('🦠 Section 6 — Disease Observation');
        $show->field('leaf_spot_level', 'Leaf Spot');
        $show->field('blight_level', 'Blight');
        $show->field('rust_level', 'Rust');
        $show->field('wilt_level', 'Wilt');
        $show->field('mosaic_virus_level', 'Mosaic Virus');
        $show->field('other_diseases_text', 'Other Diseases (Notes)');

        // Section 7: Natural Enemies
        $show->divider('🐝 Section 7 — Natural Enemies / Beneficials');
        $show->field('ladybird_beetles_level', 'Ladybird Beetles');
        $show->field('spiders_level', 'Spiders');
        $show->field('parasitoid_wasps_level', 'Parasitoid Wasps');
        $show->field('bees_pollinators_level', 'Bees / Pollinators');
        $show->field('other_beneficial_text', 'Other Beneficials (Notes)');

        // Section 8: Soil & Field Conditions
        $show->divider('🏔️ Section 8 — Soil & Field Conditions');
        $show->field('soil_condition', 'Soil Condition');
        $show->field('soil_fertility_status', 'Soil Fertility');
        $show->field('soil_erosion_signs', 'Erosion Signs');
        $show->field('weed_presence', 'Weed Presence');
        $show->field('dominant_weed_type', 'Dominant Weed Type');
        $show->field('mulching_present', 'Mulching Present')->as(fn($v) => $v ? '✅ Yes' : '❌ No');
        $show->field('crop_residue_cover', 'Crop Residue Cover');
        $show->field('water_drainage', 'Water Drainage');

        // Section 9: Problem Identification
        $show->divider('⚠️ Section 9 — Problem Identification');
        $show->field('main_problem', 'Main Problem');
        $show->field('cause_of_problem', 'Cause of Problem');
        $show->field('risk_level', 'Risk Level');
        $show->field('problem_description', 'Problem Description');

        // Section 10: Management Actions
        $show->divider('💊 Section 10 — Management Actions');
        $show->field('immediate_action', 'Immediate Action');
        $show->field('soil_management_action', 'Soil Management');
        $show->field('preventive_action', 'Preventive Action');
        $show->field('monitoring_plan', 'Monitoring Plan');
        $show->field('responsible_person', 'Responsible Person');
        $show->field('follow_up_date', 'Follow-up Date');

        // Group Discussion
        $show->divider('👥 Group Discussion');
        $show->field('mini_group_findings', 'Mini-Group Findings');
        $show->field('feedback_from_members', 'Feedback from Members');
        $show->field('final_agreed_decision', 'Final Agreed Decision');
        $show->field('key_learning_points', 'Key Learning Points');
        $show->field('facilitator_remarks', 'Facilitator Remarks');

        // Computed Scores
        $show->divider('📊 Computed Analysis Scores');
        $show->field('crop_health_score', 'Crop Health Score')->as(function ($v) {
            $score = is_numeric($v) ? $v : 0;
            $color = $score >= 75 ? '#4caf50' : ($score >= 50 ? '#ff9800' : '#f44336');
            $label = $score >= 75 ? 'Good' : ($score >= 50 ? 'Fair' : 'Poor');
            return "<span style='font-size:18px;font-weight:800;color:{$color};'>{$score}/100</span> <span style='color:{$color};font-weight:600;'>({$label})</span>";
        })->unescape();
        $show->field('pest_pressure_level', 'Overall Pest Pressure');
        $show->field('disease_pressure_level', 'Overall Disease Pressure');

        $show->divider('🕒 Audit');
        $show->field('created_at', 'Recorded At')->as(fn($d) => $d ? date('d M Y h:i A', strtotime($d)) : '—');
        $show->field('updated_at', 'Last Updated')->as(fn($d) => $d ? date('d M Y h:i A', strtotime($d)) : '—');

        return $show;
    }

    protected function form()
    {
        // Read-only shell — creation is mobile-only
        $form = new Form(new AesaCropObservation());
        $form->display('id', 'ID');
        $form->display('created_at', 'Created At');
        return $form;
    }
}
