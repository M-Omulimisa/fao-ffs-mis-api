<?php

namespace App\Admin\Controllers;

use App\Models\FfsGroup;
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
    protected $title = 'FfsGroup';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FfsGroup());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('type', __('Type'));
        $grid->column('code', __('Code'));
        $grid->column('registration_date', __('Registration date'));
        $grid->column('district_id', __('District id'));
        $grid->column('subcounty_id', __('Subcounty id'));
        $grid->column('parish_id', __('Parish id'));
        $grid->column('village', __('Village'));
        $grid->column('meeting_venue', __('Meeting venue'));
        $grid->column('meeting_day', __('Meeting day'));
        $grid->column('meeting_frequency', __('Meeting frequency'));
        $grid->column('primary_value_chain', __('Primary value chain'));
        $grid->column('secondary_value_chains', __('Secondary value chains'));
        $grid->column('total_members', __('Total members'));
        $grid->column('male_members', __('Male members'));
        $grid->column('female_members', __('Female members'));
        $grid->column('youth_members', __('Youth members'));
        $grid->column('pwd_members', __('Pwd members'));
        $grid->column('facilitator_id', __('Facilitator id'));
        $grid->column('contact_person_name', __('Contact person name'));
        $grid->column('contact_person_phone', __('Contact person phone'));
        $grid->column('latitude', __('Latitude'));
        $grid->column('longitude', __('Longitude'));
        $grid->column('status', __('Status'));
        $grid->column('cycle_number', __('Cycle number'));
        $grid->column('cycle_start_date', __('Cycle start date'));
        $grid->column('cycle_end_date', __('Cycle end date'));
        $grid->column('description', __('Description'));
        $grid->column('objectives', __('Objectives'));
        $grid->column('achievements', __('Achievements'));
        $grid->column('challenges', __('Challenges'));
        $grid->column('photo', __('Photo'));
        $grid->column('created_by_id', __('Created by id'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('deleted_at', __('Deleted at'));

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

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('type', __('Type'));
        $show->field('code', __('Code'));
        $show->field('registration_date', __('Registration date'));
        $show->field('district_id', __('District id'));
        $show->field('subcounty_id', __('Subcounty id'));
        $show->field('parish_id', __('Parish id'));
        $show->field('village', __('Village'));
        $show->field('meeting_venue', __('Meeting venue'));
        $show->field('meeting_day', __('Meeting day'));
        $show->field('meeting_frequency', __('Meeting frequency'));
        $show->field('primary_value_chain', __('Primary value chain'));
        $show->field('secondary_value_chains', __('Secondary value chains'));
        $show->field('total_members', __('Total members'));
        $show->field('male_members', __('Male members'));
        $show->field('female_members', __('Female members'));
        $show->field('youth_members', __('Youth members'));
        $show->field('pwd_members', __('Pwd members'));
        $show->field('facilitator_id', __('Facilitator id'));
        $show->field('contact_person_name', __('Contact person name'));
        $show->field('contact_person_phone', __('Contact person phone'));
        $show->field('latitude', __('Latitude'));
        $show->field('longitude', __('Longitude'));
        $show->field('status', __('Status'));
        $show->field('cycle_number', __('Cycle number'));
        $show->field('cycle_start_date', __('Cycle start date'));
        $show->field('cycle_end_date', __('Cycle end date'));
        $show->field('description', __('Description'));
        $show->field('objectives', __('Objectives'));
        $show->field('achievements', __('Achievements'));
        $show->field('challenges', __('Challenges'));
        $show->field('photo', __('Photo'));
        $show->field('created_by_id', __('Created by id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('deleted_at', __('Deleted at'));

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

        $form->text('name', __('Name'));
        $form->text('type', __('Type'))->default('FFS');
        $form->text('code', __('Code'));
        $form->date('registration_date', __('Registration date'))->default(date('Y-m-d'));
        $form->number('district_id', __('District id'));
        $form->number('subcounty_id', __('Subcounty id'));
        $form->number('parish_id', __('Parish id'));
        $form->text('village', __('Village'));
        $form->text('meeting_venue', __('Meeting venue'));
        $form->text('meeting_day', __('Meeting day'));
        $form->text('meeting_frequency', __('Meeting frequency'))->default('Weekly');
        $form->text('primary_value_chain', __('Primary value chain'));
        $form->textarea('secondary_value_chains', __('Secondary value chains'));
        $form->number('total_members', __('Total members'));
        $form->number('male_members', __('Male members'));
        $form->number('female_members', __('Female members'));
        $form->number('youth_members', __('Youth members'));
        $form->number('pwd_members', __('Pwd members'));
        $form->number('facilitator_id', __('Facilitator id'));
        $form->text('contact_person_name', __('Contact person name'));
        $form->text('contact_person_phone', __('Contact person phone'));
        $form->decimal('latitude', __('Latitude'));
        $form->decimal('longitude', __('Longitude'));
        $form->text('status', __('Status'))->default('Active');
        $form->number('cycle_number', __('Cycle number'))->default(1);
        $form->date('cycle_start_date', __('Cycle start date'))->default(date('Y-m-d'));
        $form->date('cycle_end_date', __('Cycle end date'))->default(date('Y-m-d'));
        $form->textarea('description', __('Description'));
        $form->textarea('objectives', __('Objectives'));
        $form->textarea('achievements', __('Achievements'));
        $form->textarea('challenges', __('Challenges'));
        $form->text('photo', __('Photo'));
        $form->number('created_by_id', __('Created by id'));

        return $form;
    }
}
