<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MemberController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'User';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());

        $grid->column('id', __('Id'));
        $grid->column('member_code', __('Member code'));
        $grid->column('username', __('Username'));
        $grid->column('password', __('Password'));
        $grid->column('first_name', __('First name'));
        $grid->column('last_name', __('Last name'));
        $grid->column('reg_date', __('Reg date'));
        $grid->column('last_seen', __('Last seen'));
        $grid->column('email', __('Email'));
        $grid->column('approved', __('Approved'));
        $grid->column('profile_photo', __('Profile photo'));
        $grid->column('user_type', __('User type'));
        $grid->column('registered_by_id', __('Registered by id'));
        $grid->column('created_by_id', __('Created by id'));
        $grid->column('is_membership_paid', __('Is membership paid'));
        $grid->column('membership_paid_at', __('Membership paid at'));
        $grid->column('membership_amount', __('Membership amount'));
        $grid->column('membership_payment_id', __('Membership payment id'));
        $grid->column('membership_type', __('Membership type'));
        $grid->column('membership_expiry_date', __('Membership expiry date'));
        $grid->column('sex', __('Sex'));
        $grid->column('marital_status', __('Marital status'));
        $grid->column('household_size', __('Household size'));
        $grid->column('reg_number', __('Reg number'));
        $grid->column('country', __('Country'));
        $grid->column('tribe', __('Tribe'));
        $grid->column('father_name', __('Father name'));
        $grid->column('mother_name', __('Mother name'));
        $grid->column('child_1', __('Child 1'));
        $grid->column('child_2', __('Child 2'));
        $grid->column('child_3', __('Child 3'));
        $grid->column('child_4', __('Child 4'));
        $grid->column('sponsor_id', __('Sponsor id'));
        $grid->column('occupation', __('Occupation'));
        $grid->column('profile_photo_large', __('Profile photo large'));
        $grid->column('phone_number', __('Phone number'));
        $grid->column('phone_number_2', __('Phone number 2'));
        $grid->column('emergency_contact_name', __('Emergency contact name'));
        $grid->column('emergency_contact_phone', __('Emergency contact phone'));
        $grid->column('location_lat', __('Location lat'));
        $grid->column('location_long', __('Location long'));
        $grid->column('facebook', __('Facebook'));
        $grid->column('twitter', __('Twitter'));
        $grid->column('whatsapp', __('Whatsapp'));
        $grid->column('linkedin', __('Linkedin'));
        $grid->column('website', __('Website'));
        $grid->column('other_link', __('Other link'));
        $grid->column('cv', __('Cv'));
        $grid->column('language', __('Language'));
        $grid->column('about', __('About'));
        $grid->column('address', __('Address'));
        $grid->column('district_id', __('District id'));
        $grid->column('subcounty_id', __('Subcounty id'));
        $grid->column('parish_id', __('Parish id'));
        $grid->column('village', __('Village'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('remember_token', __('Remember token'));
        $grid->column('avatar', __('Avatar'));
        $grid->column('name', __('Name'));
        $grid->column('campus_id', __('Campus id'));
        $grid->column('complete_profile', __('Complete profile'));
        $grid->column('title', __('Title'));
        $grid->column('dob', __('Dob'));
        $grid->column('education_level', __('Education level'));
        $grid->column('intro', __('Intro'));
        $grid->column('business_name', __('Business name'));
        $grid->column('business_license_number', __('Business license number'));
        $grid->column('business_license_issue_authority', __('Business license issue authority'));
        $grid->column('business_license_issue_date', __('Business license issue date'));
        $grid->column('business_license_validity', __('Business license validity'));
        $grid->column('business_address', __('Business address'));
        $grid->column('business_phone_number', __('Business phone number'));
        $grid->column('business_whatsapp', __('Business whatsapp'));
        $grid->column('business_email', __('Business email'));
        $grid->column('business_logo', __('Business logo'));
        $grid->column('business_cover_photo', __('Business cover photo'));
        $grid->column('business_cover_details', __('Business cover details'));
        $grid->column('nin', __('Nin'));
        $grid->column('status', __('Status'));
        $grid->column('parent_1', __('Parent 1'));
        $grid->column('parent_2', __('Parent 2'));
        $grid->column('parent_3', __('Parent 3'));
        $grid->column('parent_4', __('Parent 4'));
        $grid->column('parent_5', __('Parent 5'));
        $grid->column('parent_6', __('Parent 6'));
        $grid->column('parent_7', __('Parent 7'));
        $grid->column('parent_8', __('Parent 8'));
        $grid->column('parent_9', __('Parent 9'));
        $grid->column('parent_10', __('Parent 10'));
        $grid->column('is_dtehm_member', __('Is dtehm member'));
        $grid->column('dtehm_membership_paid_at', __('Dtehm membership paid at'));
        $grid->column('dtehm_membership_amount', __('Dtehm membership amount'));
        $grid->column('dtehm_membership_payment_id', __('Dtehm membership payment id'));
        $grid->column('is_dip_member', __('Is dip member'));
        $grid->column('dtehm_member_id', __('Dtehm member id'));
        $grid->column('dtehm_member_membership_date', __('Dtehm member membership date'));
        $grid->column('dtehm_membership_is_paid', __('Dtehm membership is paid'));
        $grid->column('dtehm_membership_paid_date', __('Dtehm membership paid date'));
        $grid->column('dtehm_membership_paid_amount', __('Dtehm membership paid amount'));
        $grid->column('disabilities', __('Disabilities'));
        $grid->column('skills', __('Skills'));
        $grid->column('remarks', __('Remarks'));

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
        $show->field('member_code', __('Member code'));
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
        $show->field('registered_by_id', __('Registered by id'));
        $show->field('created_by_id', __('Created by id'));
        $show->field('is_membership_paid', __('Is membership paid'));
        $show->field('membership_paid_at', __('Membership paid at'));
        $show->field('membership_amount', __('Membership amount'));
        $show->field('membership_payment_id', __('Membership payment id'));
        $show->field('membership_type', __('Membership type'));
        $show->field('membership_expiry_date', __('Membership expiry date'));
        $show->field('sex', __('Sex'));
        $show->field('marital_status', __('Marital status'));
        $show->field('household_size', __('Household size'));
        $show->field('reg_number', __('Reg number'));
        $show->field('country', __('Country'));
        $show->field('tribe', __('Tribe'));
        $show->field('father_name', __('Father name'));
        $show->field('mother_name', __('Mother name'));
        $show->field('child_1', __('Child 1'));
        $show->field('child_2', __('Child 2'));
        $show->field('child_3', __('Child 3'));
        $show->field('child_4', __('Child 4'));
        $show->field('sponsor_id', __('Sponsor id'));
        $show->field('occupation', __('Occupation'));
        $show->field('profile_photo_large', __('Profile photo large'));
        $show->field('phone_number', __('Phone number'));
        $show->field('phone_number_2', __('Phone number 2'));
        $show->field('emergency_contact_name', __('Emergency contact name'));
        $show->field('emergency_contact_phone', __('Emergency contact phone'));
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
        $show->field('district_id', __('District id'));
        $show->field('subcounty_id', __('Subcounty id'));
        $show->field('parish_id', __('Parish id'));
        $show->field('village', __('Village'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('remember_token', __('Remember token'));
        $show->field('avatar', __('Avatar'));
        $show->field('name', __('Name'));
        $show->field('campus_id', __('Campus id'));
        $show->field('complete_profile', __('Complete profile'));
        $show->field('title', __('Title'));
        $show->field('dob', __('Dob'));
        $show->field('education_level', __('Education level'));
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
        $show->field('nin', __('Nin'));
        $show->field('status', __('Status'));
        $show->field('parent_1', __('Parent 1'));
        $show->field('parent_2', __('Parent 2'));
        $show->field('parent_3', __('Parent 3'));
        $show->field('parent_4', __('Parent 4'));
        $show->field('parent_5', __('Parent 5'));
        $show->field('parent_6', __('Parent 6'));
        $show->field('parent_7', __('Parent 7'));
        $show->field('parent_8', __('Parent 8'));
        $show->field('parent_9', __('Parent 9'));
        $show->field('parent_10', __('Parent 10'));
        $show->field('is_dtehm_member', __('Is dtehm member'));
        $show->field('dtehm_membership_paid_at', __('Dtehm membership paid at'));
        $show->field('dtehm_membership_amount', __('Dtehm membership amount'));
        $show->field('dtehm_membership_payment_id', __('Dtehm membership payment id'));
        $show->field('is_dip_member', __('Is dip member'));
        $show->field('dtehm_member_id', __('Dtehm member id'));
        $show->field('dtehm_member_membership_date', __('Dtehm member membership date'));
        $show->field('dtehm_membership_is_paid', __('Dtehm membership is paid'));
        $show->field('dtehm_membership_paid_date', __('Dtehm membership paid date'));
        $show->field('dtehm_membership_paid_amount', __('Dtehm membership paid amount'));
        $show->field('disabilities', __('Disabilities'));
        $show->field('skills', __('Skills'));
        $show->field('remarks', __('Remarks'));

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

        $form->text('member_code', __('Member code'));
        $form->text('username', __('Username'));
        $form->textarea('password', __('Password'));
        $form->text('first_name', __('First name'));
        $form->text('last_name', __('Last name'));
        $form->text('reg_date', __('Reg date'));
        $form->text('last_seen', __('Last seen'));
        $form->email('email', __('Email'));
        $form->switch('approved', __('Approved'));
        $form->text('profile_photo', __('Profile photo'));
        $form->text('user_type', __('User type'))->default('Customer');
        $form->number('registered_by_id', __('Registered by id'));
        $form->number('created_by_id', __('Created by id'));
        $form->switch('is_membership_paid', __('Is membership paid'));
        $form->datetime('membership_paid_at', __('Membership paid at'))->default(date('Y-m-d H:i:s'));
        $form->decimal('membership_amount', __('Membership amount'));
        $form->number('membership_payment_id', __('Membership payment id'));
        $form->text('membership_type', __('Membership type'));
        $form->date('membership_expiry_date', __('Membership expiry date'))->default(date('Y-m-d'));
        $form->text('sex', __('Sex'));
        $form->text('marital_status', __('Marital status'));
        $form->decimal('household_size', __('Household size'));
        $form->text('reg_number', __('Reg number'));
        $form->text('country', __('Country'));
        $form->text('tribe', __('Tribe'));
        $form->text('father_name', __('Father name'));
        $form->text('mother_name', __('Mother name'));
        $form->text('child_1', __('Child 1'));
        $form->text('child_2', __('Child 2'));
        $form->text('child_3', __('Child 3'));
        $form->text('child_4', __('Child 4'));
        $form->text('sponsor_id', __('Sponsor id'));
        $form->text('occupation', __('Occupation'));
        $form->textarea('profile_photo_large', __('Profile photo large'));
        $form->text('phone_number', __('Phone number'));
        $form->text('phone_number_2', __('Phone number 2'));
        $form->text('emergency_contact_name', __('Emergency contact name'));
        $form->text('emergency_contact_phone', __('Emergency contact phone'));
        $form->text('location_lat', __('Location lat'));
        $form->text('location_long', __('Location long'));
        $form->text('facebook', __('Facebook'));
        $form->text('twitter', __('Twitter'));
        $form->text('whatsapp', __('Whatsapp'));
        $form->text('linkedin', __('Linkedin'));
        $form->text('website', __('Website'));
        $form->text('other_link', __('Other link'));
        $form->text('cv', __('Cv'));
        $form->text('language', __('Language'));
        $form->text('about', __('About'));
        $form->text('address', __('Address'));
        $form->number('district_id', __('District id'));
        $form->number('subcounty_id', __('Subcounty id'));
        $form->number('parish_id', __('Parish id'));
        $form->text('village', __('Village'));
        $form->text('remember_token', __('Remember token'));
        $form->textarea('avatar', __('Avatar'));
        $form->text('name', __('Name'));
        $form->number('campus_id', __('Campus id'))->default(1);
        $form->text('complete_profile', __('Complete profile'));
        $form->text('title', __('Title'));
        $form->datetime('dob', __('Dob'))->default(date('Y-m-d H:i:s'));
        $form->text('education_level', __('Education level'));
        $form->textarea('intro', __('Intro'));
        $form->textarea('business_name', __('Business name'));
        $form->textarea('business_license_number', __('Business license number'));
        $form->textarea('business_license_issue_authority', __('Business license issue authority'));
        $form->textarea('business_license_issue_date', __('Business license issue date'));
        $form->textarea('business_license_validity', __('Business license validity'));
        $form->textarea('business_address', __('Business address'));
        $form->textarea('business_phone_number', __('Business phone number'));
        $form->textarea('business_whatsapp', __('Business whatsapp'));
        $form->textarea('business_email', __('Business email'));
        $form->textarea('business_logo', __('Business logo'));
        $form->textarea('business_cover_photo', __('Business cover photo'));
        $form->textarea('business_cover_details', __('Business cover details'));
        $form->textarea('nin', __('Nin'));
        $form->text('status', __('Status'))->default('Active');
        $form->number('parent_1', __('Parent 1'));
        $form->number('parent_2', __('Parent 2'));
        $form->number('parent_3', __('Parent 3'));
        $form->number('parent_4', __('Parent 4'));
        $form->number('parent_5', __('Parent 5'));
        $form->number('parent_6', __('Parent 6'));
        $form->number('parent_7', __('Parent 7'));
        $form->number('parent_8', __('Parent 8'));
        $form->number('parent_9', __('Parent 9'));
        $form->number('parent_10', __('Parent 10'));
        $form->text('is_dtehm_member', __('Is dtehm member'))->default('No');
        $form->datetime('dtehm_membership_paid_at', __('Dtehm membership paid at'))->default(date('Y-m-d H:i:s'));
        $form->decimal('dtehm_membership_amount', __('Dtehm membership amount'));
        $form->number('dtehm_membership_payment_id', __('Dtehm membership payment id'));
        $form->text('is_dip_member', __('Is dip member'))->default('No');
        $form->text('dtehm_member_id', __('Dtehm member id'));
        $form->datetime('dtehm_member_membership_date', __('Dtehm member membership date'))->default(date('Y-m-d H:i:s'));
        $form->text('dtehm_membership_is_paid', __('Dtehm membership is paid'))->default('No');
        $form->datetime('dtehm_membership_paid_date', __('Dtehm membership paid date'))->default(date('Y-m-d H:i:s'));
        $form->decimal('dtehm_membership_paid_amount', __('Dtehm membership paid amount'));
        $form->textarea('disabilities', __('Disabilities'));
        $form->textarea('skills', __('Skills'));
        $form->textarea('remarks', __('Remarks'));

        return $form;
    }
}
