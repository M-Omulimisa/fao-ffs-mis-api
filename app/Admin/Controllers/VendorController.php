<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VendorController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vendors Registration';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->model([
            'status' => 'Pending',
        ])->orderBy('id', 'desc');
        $grid->disableBatchActions();
        $grid->column('id', __('Id'))->sortable();
        $grid->column('first_name', __('First name'))->sortable();
        $grid->column('last_name', __('Last name'))->sortable();
        $grid->column('email', __('Email'))->sortable();
        $grid->column('profile_photo', __('Profile photo'))->hide();
        $grid->column('user_type', __('User Type'))
            ->label(
                [
                    'admin' => 'primary',
                    'Vendor' => 'success',
                    'regular' => 'warning',
                ]
            )
            ->filter(
                [
                    'admin' => 'Admin',
                    'Vendor' => 'Vendor',
                    'regular' => 'Regular',
                ]
            )
            ->sortable();

        $grid->column('status', __('Status'))
            ->label(
                [
                    'Active' => 'success',
                    'Pending' => 'warning',
                    'Banned' => 'danger',
                ],
                'Active'
            )
            ->filter(
                [
                    'Active' => 'Active',
                    'Pending' => 'Pending',
                    'Banned' => 'Banned',
                ]
            );


        $grid->column('sex', __('Sex'));
        $grid->column('phone_number', __('Phone number'))->sortable();
        $grid->column('business_name', __('Business name'))->sortable();
        $grid->column('business_license_number', __('Business license number'))->hide();
        $grid->column('business_license_issue_authority', __('Business license issue authority'))->hide();
        $grid->column('business_license_issue_date', __('Business license issue date'))->hide();
        $grid->column('business_license_validity', __('Business license validity'))->hide();
        $grid->column('business_phone_number', __('Business phone number'))->hide();
        $grid->column('business_whatsapp', __('Business whatsapp'))->hide();
        $grid->column('business_email', __('Business email'))->hide();
        $grid->column('business_logo', __('Business logo'))->hide();
        $grid->column('business_cover_photo', __('Business cover photo'))->hide();
        $grid->column('business_cover_details', __('Business cover details'))->hide();


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
        $show = new Show(User::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('username', __('Username'));
        $show->field('password', __('Password'));
        $show->field('first_name', __('First name'));
        $show->field('last_name', __('Last name'));
        $show->field('reg_date', __('Reg date'));
        $show->field('last_seen', __('Last seen'));
        $show->field('email', __('Email'));
        $show->field('approved', __('Approved'));
        $show->field('profile_photo', __('Profile photo'));
        $show->field('user_type', __('User type'));
        $show->field('sex', __('Sex'));
        $show->field('reg_number', __('Reg number'));
        $show->field('country', __('Country'));
        $show->field('occupation', __('Occupation'));
        $show->field('profile_photo_large', __('Profile photo large'));
        $show->field('phone_number', __('Phone number'));
        $show->field('location_lat', __('Location lat'));
        $show->field('location_long', __('Location long'));
        $show->field('facebook', __('Facebook'));
        $show->field('twitter', __('Twitter'));
        $show->field('whatsapp', __('Whatsapp'));
        $show->field('linkedin', __('Linkedin'));
        $show->field('website', __('Website'));
        $show->field('other_link', __('Other link'));
        $show->field('cv', __('Cv'));
        $show->field('language', __('Language'));
        $show->field('about', __('About'));
        $show->field('address', __('Address'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('remember_token', __('Remember token'));
        $show->field('avatar', __('Avatar'));
        $show->field('name', __('Name'));
        $show->field('campus_id', __('Campus id'));
        $show->field('complete_profile', __('Complete profile'));
        $show->field('title', __('Title'));
        $show->field('dob', __('Dob'));
        $show->field('intro', __('Intro'));
        $show->field('business_name', __('Business name'));
        $show->field('business_license_number', __('Business license number'));
        $show->field('business_license_issue_authority', __('Business license issue authority'));
        $show->field('business_license_issue_date', __('Business license issue date'));
        $show->field('business_license_validity', __('Business license validity'));
        $show->field('business_address', __('Business address'));
        $show->field('business_phone_number', __('Business phone number'));
        $show->field('business_whatsapp', __('Business whatsapp'));
        $show->field('business_email', __('Business email'));
        $show->field('business_logo', __('Business logo'));
        $show->field('business_cover_photo', __('Business cover photo'));
        $show->field('business_cover_details', __('Business cover details'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());


        $form->text('first_name', __('First name'))->rules('required');
        $form->text('last_name', __('Last name'))->rules('required');
        $form->text('campus_id', __('ID Number'))->default(1);
        $form->text('business_name', __('Business name'));
        $form->text('business_license_number', __('Business license number'));
        $form->text('business_license_issue_authority', __('Business license issue authority'));
        $form->text('business_license_issue_date', __('Business license issue date'));
        $form->text('business_license_validity', __('Business license validity'));
        $form->text('business_address', __('Business address'));
        $form->text('business_phone_number', __('Business phone number'));
        $form->text('business_whatsapp', __('Business whatsapp'));
        $form->text('business_email', __('Business email'));
        $form->image('business_logo', __('Business logo'));
        $form->text('business_cover_photo', __('Business cover photo'));
        $form->text('business_cover_details', __('Business cover details'));
        $form->radioCard('user_type', __('User type'))->default('regular')
            ->options(
                [
                    'admin' => 'Admin',
                    'Vendor' => 'Vendor',
                    'regular' => 'Regular',
                ]
            )
            ->rules('required');
        $form->radioCard('status', __('status'))->default('regular')
            ->options(
                [
                    'Active' => 'Active',
                    'Pending' => 'Pending',
                    'Banned' => 'Banned',
                ]
            )
            ->rules('required');
        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        return $form;
    }
}
