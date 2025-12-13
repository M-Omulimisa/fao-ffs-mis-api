<?php

namespace App\Admin\Controllers;

use App\Models\VslaMeetingAttendance;
use App\Models\VslaMeeting;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VslaMeetingAttendanceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'VSLA Meeting Attendance';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VslaMeetingAttendance());

        $grid->model()->with(['meeting.cycle', 'meeting.group', 'member'])->orderBy('id', 'desc');
        
        $grid->disableCreateButton(); // Attendance created from meetings
        $grid->disableExport();

        $grid->quickSearch('member.name')->placeholder('Search by member name');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->equal('meeting_id', 'Meeting')
                ->select(VslaMeeting::with('group')->orderBy('id', 'desc')->limit(50)->get()->pluck('meeting_info', 'id'));

            $filter->equal('member_id', 'Member')
                ->select(User::orderBy('name')->pluck('name', 'id'));

            $filter->equal('is_present', 'Status')->select([
                1 => 'Present',
                0 => 'Absent',
            ]);

            $filter->between('created_at', 'Date Range')->datetime();
        });

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('meeting.cycle.ffs_group.name', __('VSLA Group'))
            ->display(function () {
                if (!$this->meeting || !$this->meeting->cycle || !$this->meeting->cycle->ffs_group) {
                    return '<span class="label label-default">No Group</span>';
                }
                $group = $this->meeting->cycle->ffs_group;
                $code = $group->code ?? '';
                $name = \Illuminate\Support\Str::limit($group->name, 18);
                return "<span class='label label-info'>{$name}" . ($code ? " ({$code})" : '') . "</span>";
            });
        
        $grid->column('meeting.cycle.title', __('Cycle'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 18);
            });

        $grid->column('meeting.meeting_info', __('Meeting'))
            ->display(function () {
                $number = $this->meeting->meeting_number ?? '-';
                $date = $this->meeting->meeting_date ? date('M d', strtotime($this->meeting->meeting_date)) : '-';
                return "<strong>#{$number}</strong><br><small>{$date}</small>";
            });

        $grid->column('member.name', __('Member Details'))
            ->display(function ($name) {
                $phone = $this->member->phone_number ?? 'N/A';
                $role = '';
                if ($this->member->is_group_admin === 'Yes') $role = ' 👑';
                elseif ($this->member->is_group_secretary === 'Yes') $role = ' 📝';
                elseif ($this->member->is_group_treasurer === 'Yes') $role = ' 💰';
                return "<div>"
                    . "<strong>" . \Illuminate\Support\Str::limit($name, 25) . "{$role}</strong>"
                    . "<br><small class='text-muted'>{$phone}</small>"
                    . "</div>";
            });

        $grid->column('is_present', __('Status'))
            ->display(function ($isPresent) {
                if ($isPresent) {
                    return '<span class="label label-success" style="font-size:13px;">✓ Present</span>';
                }
                $reason = $this->absent_reason ? \Illuminate\Support\Str::limit($this->absent_reason, 30) : 'No reason';
                return "<div>"
                    . '<span class="label label-danger" style="font-size:13px;">✗ Absent</span>'
                    . "<br><small class='text-muted'>{$reason}</small>"
                    . "</div>";
            })
            ->sortable();

        $grid->column('created_at', __('Recorded'))
            ->display(function ($date) {
                return date('M d, Y H:i', strtotime($date));
            })
            ->sortable();

        $grid->actions(function ($actions) {
            $actions->disableEdit(); // Attendance is historical data
            $actions->disableDelete();
        });

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
        $show = new Show(VslaMeetingAttendance::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('local_id', __('Local ID'));
        
        $show->divider('Meeting Information');
        $show->field('meeting.cycle.title', __('Cycle'));
        $show->field('meeting.group.name', __('Group'));
        $show->field('meeting.group.code', __('Group Code'));
        $show->field('meeting.meeting_number', __('Meeting Number'));
        $show->field('meeting.meeting_date', __('Meeting Date'));
        $show->field('meeting.processing_status', __('Meeting Status'));
        
        $show->divider('Member Information');
        $show->field('member.name', __('Member Name'));
        $show->field('member.email', __('Email'));
        $show->field('member.phone_number', __('Phone'));
        $show->field('member.gender', __('Gender'));
        
        $show->divider('Attendance Information');
        $show->field('is_present', __('Attendance Status'))->as(function ($val) {
            return $val ? 'Present' : 'Absent';
        });
        $show->field('absent_reason', __('Absence Reason'))->as(function ($val) {
            return $val ?: 'N/A';
        });
        
        $show->divider('Meeting Summary');
        $show->field('meeting.members_present', __('Total Present'));
        $show->field('meeting.total_members', __('Total Members'));
        $show->field('meeting.attendance_rate', __('Attendance Rate'))->as(function ($rate) {
            return number_format($rate, 1) . '%';
        });
        $show->field('meeting.total_cash_collected', __('Total Cash Collected'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->divider('Record Information');
        $show->field('created_at', __('Recorded At'));
        $show->field('updated_at', __('Last Updated'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new VslaMeetingAttendance());

        // Attendance should not be manually created or edited
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->display('id', __('ID'));
        $form->display('member.name', __('Member'));
        $form->display('meeting.meeting_number', __('Meeting Number'));
        $form->display('is_present', __('Status'))->with(function ($val) {
            return $val ? 'Present' : 'Absent';
        });

        return $form;
    }
}
