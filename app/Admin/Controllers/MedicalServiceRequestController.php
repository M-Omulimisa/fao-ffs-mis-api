<?php

namespace App\Admin\Controllers;

use App\Models\MedicalServiceRequest;
use App\Models\User;
use App\Models\InsuranceSubscription;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MedicalServiceRequestController extends AdminController
{
    protected $title = 'Medical Service Requests';

    protected function grid()
    {
        $grid = new Grid(new MedicalServiceRequest());
        
        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        $grid->disableCreateButton();
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user_id', 'User')
                ->select(User::pluck('name', 'id'));
            $filter->equal('service_category', 'Category')->select([
                'outpatient' => 'Outpatient',
                'inpatient' => 'Inpatient',
                'emergency' => 'Emergency',
                'dental' => 'Dental',
                'optical' => 'Optical',
                'maternity' => 'Maternity',
                'laboratory' => 'Laboratory',
                'pharmacy' => 'Pharmacy',
                'other' => 'Other',
            ]);
            $filter->equal('service_type', 'Service Type');
            $filter->equal('urgency_level', 'Urgency')->select([
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
                'critical' => 'Critical',
            ]);
            $filter->equal('status', 'Status')->select([
                'pending' => 'Pending',
                'processing' => 'Processing',
                'scheduled' => 'Scheduled',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
                'rejected' => 'Rejected',
            ]);
        });

        $grid->column('id', __('ID'))->sortable();
        $grid->column('reference_number', __('Ref#'))->sortable();
        $grid->column('user.name', __('User'))->sortable();
        $grid->column('service_category', __('Category'))->sortable();
        $grid->column('service_type', __('Service'))->sortable();
        $grid->column('urgency_level', __('Urgency'))->label([
            'low' => 'default',
            'medium' => 'info',
            'high' => 'warning',
            'critical' => 'danger',
        ])->sortable();
        $grid->column('status', __('Status'))->label([
            'pending' => 'warning',
            'processing' => 'primary',
            'scheduled' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            'rejected' => 'default',
        ])->sortable();
        $grid->column('estimated_cost', __('Est. Cost'))->display(function ($cost) {
            return $cost ? 'UGX ' . number_format($cost, 0) : 'N/A';
        });
        $grid->column('scheduled_date', __('Scheduled'))
            ->display(function ($date) {
                return $date ? date('d M Y', strtotime($date)) : '-';
            })
            ->sortable();
        $grid->column('created_at', __('Date'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(MedicalServiceRequest::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('reference_number', __('Reference Number'));
        $show->field('user.name', __('User'));
        $show->field('insuranceSubscription.policy_number', __('Policy #'));
        $show->field('service_category', __('Category'));
        $show->field('service_type', __('Service Type'));
        $show->field('urgency_level', __('Urgency Level'));
        $show->field('symptoms_description', __('Symptoms'));
        $show->field('additional_notes', __('Additional Notes'));
        $show->field('preferred_hospital', __('Preferred Hospital'));
        $show->field('preferred_doctor', __('Preferred Doctor'));
        $show->field('contact_phone', __('Contact Phone'));
        $show->field('contact_email', __('Contact Email'));
        $show->field('contact_address', __('Contact Address'));
        $show->field('status', __('Status'));
        $show->field('assigned_hospital', __('Assigned Hospital'));
        $show->field('assigned_doctor', __('Assigned Doctor'));
        $show->field('scheduled_date', __('Scheduled Date'));
        $show->field('scheduled_time', __('Scheduled Time'));
        $show->field('estimated_cost', __('Estimated Cost'))->as(function ($cost) {
            return $cost ? 'UGX ' . number_format($cost, 0) : 'N/A';
        });
        $show->field('insurance_coverage', __('Insurance Coverage'))->as(function ($amt) {
            return $amt ? 'UGX ' . number_format($amt, 0) : 'N/A';
        });
        $show->field('patient_payment', __('Patient Payment'))->as(function ($amt) {
            return $amt ? 'UGX ' . number_format($amt, 0) : 'N/A';
        });
        $show->field('admin_feedback', __('Admin Feedback'));
        $show->field('created_at', __('Created At'));
        return $show;
    }

    protected function form()
    {
        $form = new Form(new MedicalServiceRequest());
        
        $form->display('reference_number', __('Reference #'));
        $form->display('user.name', __('User'));
        $form->display('service_category', __('Category'));
        $form->display('service_type', __('Service Type'));
        $form->display('urgency_level', __('Urgency Level'));
        $form->display('symptoms_description', __('Symptoms'));
        $form->display('contact_phone', __('Contact Phone'));
        $form->display('contact_email', __('Contact Email'));
        
        $form->select('status', __('Status'))
            ->options([
                'pending' => 'Pending',
                'processing' => 'Processing',
                'scheduled' => 'Scheduled',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
                'rejected' => 'Rejected',
            ])
            ->rules('required');
        
        $form->text('assigned_hospital', __('Assigned Hospital'));
        $form->text('assigned_doctor', __('Assigned Doctor'));
        $form->date('scheduled_date', __('Scheduled Date'));
        $form->time('scheduled_time', __('Scheduled Time'));
        
        $form->decimal('estimated_cost', __('Estimated Cost (UGX)'));
        $form->decimal('insurance_coverage', __('Insurance Coverage (UGX)'));
        $form->decimal('patient_payment', __('Patient Payment (UGX)'));
        
        $form->textarea('admin_feedback', __('Admin Feedback'))->rows(4);
        
        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        
        return $form;
    }
}
