<?php

namespace App\Admin\Controllers;

use App\Models\NotificationModel;
use App\Models\User;
use App\Models\OneSignalDevice;
use App\Services\OneSignalService;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'OneSignal Push Notifications Management';

    /**
     * Enhanced dashboard with analytics
     */
    public function index(Content $content)
    {
        return $content
            ->title('Push Notifications Dashboard')
            ->description('Comprehensive OneSignal management and analytics')
            ->row(function (Row $row) {
                // Analytics widgets
                $row->column(3, new InfoBox('Total Notifications', 'bell', 'blue', '/admin/notifications', NotificationModel::count()));
                $row->column(3, new InfoBox('Sent Today', 'paper-plane', 'green', '', NotificationModel::whereDate('sent_at', today())->count()));
                $row->column(3, new InfoBox('Registered Devices', 'mobile', 'yellow', '/admin/onesignal-devices', OneSignalDevice::count()));
                $row->column(3, new InfoBox('Users with Devices', 'users', 'red', '', User::whereHas('oneSignalDevices')->count()));
            })
            ->row(function (Row $row) {
                // Quick actions
                $row->column(6, $this->quickNotificationForm());
                $row->column(6, $this->connectionStatus());
            })
            ->row(function (Row $row) {
                // Notification grid
                $row->column(12, (new Box('Recent Notifications', $this->grid()->render())));
            });
    }

    /**
     * Quick notification form widget
     */
    protected function quickNotificationForm()
    {
        $form = '
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Send Notification</h3>
            </div>
            <div class="box-body">
                <form method="POST" action="' . admin_url('notifications/quick-send') . '">
                    ' . csrf_field() . '
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" class="form-control">
                            <option value="general">General</option>
                            <option value="promotion">Promotion</option>
                            <option value="order">Order Update</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Send to All Users</button>
                </form>
            </div>
        </div>';

        return new Box('Quick Send', $form);
    }

    /**
     * Connection status widget
     */
    protected function connectionStatus()
    {
        $oneSignal = new OneSignalService();
        $stats = $oneSignal->getAppStats();

        $statusColor = $stats['success'] ? 'success' : 'danger';
        $statusText = $stats['success'] ? 'Connected' : 'Disconnected';

        $content = '
        <div class="info-box bg-' . $statusColor . '">
            <span class="info-box-icon"><i class="fa fa-wifi"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">OneSignal Status</span>
                <span class="info-box-number">' . $statusText . '</span>';

        if ($stats['success']) {
            $content .= '
                <div class="progress">
                    <div class="progress-bar" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    Total: ' . $stats['total_users'] . ' | Messageable: ' . $stats['messageable_users'] . '
                </span>';
        } else {
            $content .= '
                <span class="progress-description">Error: ' . ($stats['error'] ?? 'Unknown') . '</span>';
        }

        $content .= '
            </div>
        </div>';

        return new Box('Connection Status', $content);
    }

    /**
     * Enhanced grid with better functionality
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new NotificationModel());

        // Order by latest first
        $grid->model()->latest();

        $grid->column('id', __('ID'))->sortable();
        $grid->column('title', __('Title'))->limit(50)->help('Click to view full details');
        $grid->column('message', __('Message'))->limit(80)->display(function ($message) {
            return '<span title="' . htmlspecialchars($message) . '">' . \Illuminate\Support\Str::limit($message, 80) . '</span>';
        });

        $grid->column('type', __('Type'))->label([
            'general' => 'primary',
            'promotion' => 'success',
            'order' => 'warning',
            'urgent' => 'danger',
        ]);

        $grid->column('target_description', __('Target'))->display(function () {
            return $this->getTargetDescriptionAttribute();
        });

        $grid->column('recipients', __('Recipients'))->badge('green')->help('Number of devices that received the notification');

        $grid->column('status', __('Status'))
        ->filter([
            'pending' => 'pending',
            'sent' => 'sent',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            'scheduled' => 'scheduled',
        ])->editable('select', [
            'pending' => 'Pending',
            'sent' => 'Sent',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'scheduled' => 'Scheduled',
        ])->sortable();

        $grid->column("delivery_stats", __("Performance"))->display(function () {
            if ($this->status !== "sent") return "-";
            return "<small>📤 " . ($this->recipients ?? 0) . " delivered</small>";
        });

        $grid->column('sent_at', __('Sent At'))->display(function ($sent_at) {
            return $sent_at ? \Carbon\Carbon::parse($sent_at)->format('M d, Y H:i') : '-';
        })->sortable();

        $grid->column('created_at', __('Created'))->display(function ($created_at) {
            return \Carbon\Carbon::parse($created_at)->format('M d, Y H:i');
        })->sortable();

        $grid->column('send', __('send'))->display(function ($created_at) {
            if ($this->status !== 'pending' && $this->status !== 'scheduled') {
                return '<span class="text-muted">N/A</span>';
            }
            if ($this->status == 'sent') {
                return '<span class="text-muted">N/A</span>';
            }
            $link = '<a href="' . url('do-send-notofocation?id=' . $this->id) . '"  target="_blank" rel="noopener"
                    class="btn btn-xs btn-success" title="Send Notification">
                    <i class="fa fa-paper-plane"></i> Send
                </a>';
            return $link;
        })->sortable();

        // Enhanced bulk actions
        $grid->tools(function ($tools) {
            $tools->append('<a href="' . admin_url('notifications/create') . '" class="btn btn-sm btn-success">
                <i class="fa fa-plus"></i> Create Notification
            </a>');
            $tools->append('<a href="' . admin_url('notifications/templates') . '" class="btn btn-sm btn-info">
                <i class="fa fa-file-text"></i> Templates
            </a>');
            $tools->append('<a href="' . admin_url('notifications/analytics') . '" class="btn btn-sm btn-primary">
                <i class="fa fa-bar-chart"></i> Analytics Dashboard
            </a>');
        });

        // Enhanced filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('title', 'Title');
            $filter->like('message', 'Message');
            $filter->equal('type', 'Type')->select([
                'general' => 'General',
                'promotion' => 'Promotion',
                'order' => 'Order',
                'urgent' => 'Urgent',
            ]);
            $filter->equal('status', 'Status')->select([
                'pending' => 'Pending',
                'sent' => 'Sent',
                'failed' => 'Failed',
                'cancelled' => 'Cancelled',
                'scheduled' => 'Scheduled',
            ]);
            $filter->between('created_at', 'Created')->datetime();
            $filter->between('sent_at', 'Sent')->datetime();
            $filter->where(function ($query) {
                $input = request('min_recipients');
                if ($input) {
                    $query->where('recipients', '>=', $input);
                }
            }, 'Min Recipients', 'min_recipients');
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
        $show = new Show(NotificationModel::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('title', __('Title'));
        $show->field('message', __('Message'));
        $show->field('type', __('Type'));

        $show->field('target_description', __('Target'));

        $show->field('recipients', __('Recipients'));
        $show->field('status', __('Status'));
        $show->field('onesignal_id', __('OneSignal ID'));
        $show->field('error_message', __('Error Message'));

        $show->field('data', __('Data'))->json();
        $show->field('url', __('Action URL'));
        $show->field('large_icon', __('Large Icon'));
        $show->field('big_picture', __('Big Picture'));

        $show->field('sent_at', __('Sent At'));
        $show->field('created_at', __('Created'));
        $show->field('updated_at', __('Updated'));

        $show->creator('Creator', function ($creator) {
            $creator->setResource('/admin/users');
            $creator->field('name');
            $creator->field('email');
        });

        return $show;
    }

    /**
     * Enhanced form with templates and scheduling
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new NotificationModel());

        // Template selection
        $form->select('template', __('Template (Optional)'))->options([
            '' => 'Custom Notification',
            'welcome' => '👋 Welcome New User',
            'order_confirmed' => '✅ Order Confirmed',
            'order_shipped' => '🚚 Order Shipped',
            'order_delivered' => '📦 Order Delivered',
            'promotion' => '🎉 Special Promotion',
            'flash_sale' => '⚡ Flash Sale Alert',
            'abandoned_cart' => '🛒 Abandoned Cart Reminder',
            'back_in_stock' => '📦 Back in Stock',
            'price_drop' => '💰 Price Drop Alert',
            'review_request' => '⭐ Review Request',
        ])->help('Select a pre-made template or create custom notification');

        $form->divider('Notification Content');

        $form->text('title', __('Title'))->required()->help('Keep it short and compelling');
        $form->textarea('message', __('Message'))->rows(4)->required()->help('Clear and actionable message');

        $form->select('type', __('Priority Level'))->options([
            'general' => '📢 General',
            'promotion' => '🎉 Promotion',
            'order' => '📦 Order Update',
            'urgent' => '🚨 Urgent',
        ])->default('general');

        $form->divider('Target Audience');

        $form->radio('target_type', __('Send To'))->options([
            'all' => 'All Registered Users',
            'active' => 'Active Users (Last 30 days)',
            'specific' => 'Specific Users',
            'segments' => 'OneSignal Segments',
            'devices' => 'Device Types',
        ])->default('all')
            ->when('specific', function (Form $form) {
                $form->multipleSelect('target_users', __('Select Users'))
                    ->options(User::pluck('name', 'id'))
                    ->help('Choose specific users to send notification to');
            })
            ->when('segments', function (Form $form) {
                $form->tags('target_segments', __('Segments'))
                    ->help('Enter OneSignal segments (e.g., All, Active Users, Premium Users)');
            })
            ->when('devices', function (Form $form) {
                $form->checkbox('target_devices', __('Device Types'))->options([
                    'android' => 'Android Devices',
                    'ios' => 'iOS Devices',
                    'web' => 'Web Push',
                ])->help('Select device types to target');
            });
        /* 

id
title
message
type
template
target_users
target_segments
filters
onesignal_id
recipients
status
error_message
data
url
large_icon
big_picture
sent_at
created_by
created_at
updated_at

target_users
target_segments
filters
onesignal_id
recipients
error_message
click_count
large_icon
big_picture
sent_at





*/
        $form->divider('Scheduling & Delivery');

        $form->radio('delivery_type', __('Delivery'))->options([
            'immediate' => 'Send Immediately',
            'scheduled' => 'Schedule for Later',
            'recurring' => 'Recurring Notification',
        ])->default('immediate')
            ->when('scheduled', function (Form $form) {
                $form->datetime('scheduled_at', __('Schedule Time'))
                    ->help('When to send this notification');
            })
            ->when('recurring', function (Form $form) {
                $form->select('recurring_pattern', __('Repeat Pattern'))->options([
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                    'monthly' => 'Monthly',
                ]);
                $form->datetime('start_at', __('Start Date'));
                $form->datetime('end_at', __('End Date (Optional)'));
            });

        $form->divider('Rich Media & Actions');

        $form->url('url', __('Action URL'))->help('URL to open when notification is tapped');

        // Enhanced image support with upload and URL options
        $form->tab('Media Type', function ($form) {
            $form->radio('icon_type', __('Large Icon Source'))->options([
                'url' => 'URL',
                'upload' => 'Upload File'
            ])->default('url')
                ->when('url', function ($form) {
                    $form->url('large_icon', __('Large Icon URL'))->help('Large icon image URL (recommended: 256x256px)');
                })
                ->when('upload', function ($form) {
                    $form->image('large_icon_upload', __('Large Icon Upload'))
                        ->disk('public')
                        ->help('Upload large icon image (recommended: 256x256px, max 5MB)');
                });

            $form->radio('picture_type', __('Big Picture Source'))->options([
                'url' => 'URL',
                'upload' => 'Upload File'
            ])->default('url')
                ->when('url', function ($form) {
                    $form->url('big_picture', __('Big Picture URL'))->help('Expandable image URL (recommended: 1024x512px)');
                })
                ->when('upload', function ($form) {
                    $form->image('big_picture_upload', __('Big Picture Upload'))
                        ->disk('public')
                        ->help('Upload expandable image (recommended: 1024x512px, max 10MB)');
                });
        });

        $form->keyValue('data', __('Custom Data'))->help('Additional data for app handling (JSON format)');

        $form->divider('Advanced Settings');

        $form->switch('send_after_time_passed', __('Time-based Delivery'))
            ->help('Only send to users who haven\'t been active recently');

        $form->number('ttl', __('Time to Live (hours)'))
            ->default(72)
            ->help('How long OneSignal should attempt delivery');

        $form->multipleSelect('priority_countries', __('Priority Countries'))
            ->options([
                'US' => 'United States',
                'UG' => 'Uganda',
                'KE' => 'Kenya',
                'TZ' => 'Tanzania',
                'GB' => 'United Kingdom',
                'CA' => 'Canada',
            ])
            ->help('Prioritize delivery to these countries');

        // Hidden fields
        $form->hidden('created_by')->default(Auth::id());
        $form->radio('status')
            ->options([
                'pending' => 'Pending',
                'sent' => 'Sent',
                'failed' => 'Failed',
                'cancelled' => 'Cancelled',
                'scheduled' => 'Scheduled',
            ])
            ->default('pending');

        // Custom JavaScript for template loading
        $form->html('
            <script>
            $(document).ready(function() {
                // Template loading functionality
                $(\'select[name="template"]\').change(function() {
                    var template = $(this).val();
                    if (template) {
                        loadTemplate(template);
                    }
                });
                
                function loadTemplate(template) {
                    var templates = {
                        "welcome": {
                            title: "Welcome to BlitXpress! 👋",
                            message: "Thank you for joining us! Start exploring amazing products and deals.",
                            type: "general"
                        },
                        "order_confirmed": {
                            title: "Order Confirmed! ✅",
                            message: "Your order has been confirmed and is being processed.",
                            type: "order"
                        },
                        "promotion": {
                            title: "Special Offer Just for You! 🎉",
                            message: "Don\'t miss out on our limited-time promotion.",
                            type: "promotion"
                        }
                        // Add more templates as needed
                    };
                    
                    if (templates[template]) {
                        $(\'input[name="title"]\').val(templates[template].title);
                        $(\'textarea[name="message"]\').val(templates[template].message);
                        $(\'select[name="type"]\').val(templates[template].type).trigger(\'change\');
                    }
                }
            });
            </script>
        ');

        // Simplified save logic - model mutators handle array conversions
        $form->saving(function (Form $form) {
            // Store values we need before processing
            $targetType = $form->target_type ?? null;
            $deliveryType = $form->delivery_type ?? null;
            $iconType = $form->icon_type ?? null;
            $pictureType = $form->picture_type ?? null;
            $largeIconUpload = $form->large_icon_upload ?? null;
            $bigPictureUpload = $form->big_picture_upload ?? null;
            $recurringPattern = $form->recurring_pattern ?? null;
            $startAt = $form->start_at ?? null;
            $endAt = $form->end_at ?? null;

            // Process target type logic
            switch ($targetType) {
                case 'all':
                    $form->target_users = [];
                    $form->target_segments = [];
                    break;
                case 'active':
                    $form->target_users = [];
                    $form->target_segments = ['Active Users'];
                    break;
                case 'specific':
                    $form->target_segments = [];
                    break;
                case 'segments':
                    $form->target_users = [];
                    break;
                case 'devices':
                    $form->target_users = [];
                    $form->target_segments = [];
                    break;
            }

            // Handle image uploads
            if ($iconType === 'upload' && $largeIconUpload) {
                $form->large_icon = asset('storage/' . $largeIconUpload);
            }
            if ($pictureType === 'upload' && $bigPictureUpload) {
                $form->big_picture = asset('storage/' . $bigPictureUpload);
            }

            // Handle scheduling
            if ($deliveryType === 'scheduled') {
                $form->status = 'scheduled';
            } elseif ($deliveryType === 'recurring') {
                $form->status = 'scheduled';
                // Store recurring pattern in data field (model mutator handles JSON conversion)
                $form->data = [
                    'recurring_pattern' => $recurringPattern,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                ];
            }

            // Set default status if not set
            if (!$form->status) {
                $form->status = 'pending';
            }
        });

        return $form;
    }

    /**
     * Custom create page with notification composer
     */
    public function create(Content $content)
    {
        return $content
            ->title('Create Push Notification')
            ->description('Compose and send push notifications to users')
            ->body($this->form());
    }

    /**
     * Enhanced quick send with better validation and tracking
     */
    public function quickSend(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'type' => 'required|in:general,promotion,order,urgent',
        ]);

        try {
            $notification = NotificationModel::create([
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            $result = $notification->send();

            if ($result['success']) {
                admin_success('✅ Notification sent successfully!', 'Delivered to ' . $result['recipients'] . ' devices');

                // Log the success
                Log::info('OneSignal notification sent successfully', [
                    'notification_id' => $notification->id,
                    'recipients' => $result['recipients'],
                    'admin_user' => Auth::id(),
                ]);
            } else {
                admin_error('❌ Failed to send notification', $result['error']);

                // Log the error
                Log::error('OneSignal notification failed', [
                    'notification_id' => $notification->id,
                    'error' => $result['error'],
                    'admin_user' => Auth::id(),
                ]);
            }
        } catch (\Exception $e) {
            admin_error('❌ System Error', 'Failed to create notification: ' . $e->getMessage());
            Log::error('Notification creation failed', [
                'error' => $e->getMessage(),
                'admin_user' => Auth::id(),
            ]);
        }

        return back();
    }

    /**
     * Send a pending notification
     */
    public function send(Request $request, $id)
    {
        try {
            $notification = NotificationModel::findOrFail($id);

            if (!in_array($notification->status, ['pending', 'scheduled'])) {
                admin_error('❌ Cannot send notification', 'Notification status is: ' . $notification->status);
                return back();
            }

            $result = $notification->send();

            if ($result['success']) {
                admin_success('✅ Notification sent successfully!', 'Delivered to ' . $result['recipients'] . ' devices');
            } else {
                admin_error('❌ Failed to send notification', $result['error']);
            }

            return back();
        } catch (\Exception $e) {
            admin_error('❌ Error sending notification', $e->getMessage());
            return back();
        }
    }

    /**
     * Cancel a pending or scheduled notification
     */
    public function cancel(Request $request, $id)
    {
        try {
            $notification = NotificationModel::findOrFail($id);

            if (!in_array($notification->status, ['pending', 'scheduled'])) {
                admin_error('❌ Cannot cancel notification', 'Notification status is: ' . $notification->status);
                return back();
            }

            $notification->update(['status' => 'cancelled']);
            admin_success('✅ Notification cancelled successfully');

            return back();
        } catch (\Exception $e) {
            admin_error('❌ Error cancelling notification', $e->getMessage());
            return back();
        }
    }

    /**
     * Device Management Dashboard
     */
    public function devices(Content $content)
    {
        return $content
            ->title('OneSignal Device Management')
            ->description('Track and manage registered devices')
            ->row(function (Row $row) {
                $stats = OneSignalDevice::getStatistics();

                $row->column(3, new InfoBox('Total Devices', 'mobile', 'blue', '', $stats['total']));
                $row->column(3, new InfoBox('Active Devices', 'check-circle', 'green', '', $stats['active']));
                $row->column(3, new InfoBox('Android', 'android', 'success', '', $stats['android']));
                $row->column(3, new InfoBox('iOS', 'apple', 'info', '', $stats['ios']));
            })
            ->row(function (Row $row) {
                $row->column(12, $this->deviceGrid());
            });
    }

    /**
     * Device grid for management
     */
    protected function deviceGrid()
    {
        $grid = new Grid(new OneSignalDevice());
        $grid->model()->with('user')->latest();

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.name', 'User')->display(function ($name) {
            return $name ?: '<span class="text-muted">Anonymous</span>';
        });
        $grid->column('player_id', 'Player ID')->limit(20);
        $grid->column('device_type', 'Platform')->display(function ($type) {
            $emoji = ['android' => '🤖', 'ios' => '📱', 'web' => '🌐'][$type] ?? '📱';
            return $emoji . ' ' . ucfirst($type);
        });
        $grid->column('device_model', 'Device')->limit(30);
        $grid->column('country', 'Country')->display(function ($country) {
            return $country ? strtoupper($country) : '-';
        });
        $grid->column('status_badge', 'Status')->display(function () {
            return $this->getStatusBadgeAttribute();
        });        $grid->column('status_badge', 'Status')->display(function () {
            return $this->getStatusBadgeAttribute();
        });
        $grid->column('last_seen_format', 'Last Seen');
        $grid->column('session_count', 'Sessions')->badge('blue');

        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->add('<a href="javascript:void(0)" class="btn btn-xs btn-primary" onclick="sendTestNotification(\'' . $actions->row->player_id . '\')">Test</a>');
        });

        $grid->filter(function ($filter) {
            $filter->like('user.name', 'User');
            $filter->equal('device_type', 'Platform')->select([
                'android' => 'Android',
                'ios' => 'iOS',
                'web' => 'Web',
            ]);
            $filter->equal('is_active', 'Status')->select([
                1 => 'Active',
                0 => 'Inactive',
            ]);
            $filter->between('last_seen_at', 'Last Seen')->datetime();
        });

        return new Box('Registered Devices', $grid->render());
    }

    /**
     * Analytics Dashboard
     */
    public function analytics(Content $content)
    {
        return $content
            ->title('OneSignal Analytics Dashboard')
            ->description('Comprehensive notification and device analytics')
            ->row(function (Row $row) {
                // Performance metrics
                $totalSent = NotificationModel::where('status', 'sent')->count();
                $totalDelivered = NotificationModel::where('status', 'sent')->sum('recipients');
                $totalClicks = NotificationModel::where('status', 'sent')->sum('click_count');
                $avgClickRate = $totalDelivered > 0 ? round(($totalClicks / $totalDelivered) * 100, 1) : 0;

                $row->column(3, new InfoBox('Notifications Sent', 'paper-plane', 'blue', '', $totalSent));
                $row->column(3, new InfoBox('Total Delivered', 'check', 'green', '', number_format($totalDelivered)));
                $row->column(3, new InfoBox('Total Clicks', 'mouse-pointer', 'yellow', '', number_format($totalClicks)));
                $row->column(3, new InfoBox('Avg Click Rate', 'percentage', 'red', '', $avgClickRate . '%'));
            })
            ->row(function (Row $row) {
                $row->column(6, $this->getNotificationChart());
                $row->column(6, $this->getDeviceChart());
            })
            ->row(function (Row $row) {
                $row->column(12, $this->getPerformanceTable());
            });
    }

    /**
     * Get notification performance chart
     */
    protected function getNotificationChart()
    {
        $data = NotificationModel::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = $data->pluck('date')->toArray();
        $values = $data->pluck('count')->toArray();

        $chart = '
        <div class="box box-primary">
            <div class="box-header">
                <h3 class="box-title">Notifications Sent (Last 30 Days)</h3>
            </div>
            <div class="box-body">
                <canvas id="notificationChart" height="100"></canvas>
            </div>
        </div>
        <script>
        $(document).ready(function() {
            var ctx = document.getElementById("notificationChart").getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: {
                    labels: ' . json_encode($labels) . ',
                    datasets: [{
                        label: "Notifications",
                        data: ' . json_encode($values) . ',
                        borderColor: "rgb(75, 192, 192)",
                        backgroundColor: "rgba(75, 192, 192, 0.1)",
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>';

        return $chart;
    }

    /**
     * Get device distribution chart
     */
    protected function getDeviceChart()
    {
        $data = OneSignalDevice::selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->get();

        $labels = $data->pluck('device_type')->toArray();
        $values = $data->pluck('count')->toArray();

        $chart = '
        <div class="box box-info">
            <div class="box-header">
                <h3 class="box-title">Device Distribution</h3>
            </div>
            <div class="box-body">
                <canvas id="deviceChart" height="100"></canvas>
            </div>
        </div>
        <script>
        $(document).ready(function() {
            var ctx = document.getElementById("deviceChart").getContext("2d");
            new Chart(ctx, {
                type: "doughnut",
                data: {
                    labels: ' . json_encode($labels) . ',
                    datasets: [{
                        data: ' . json_encode($values) . ',
                        backgroundColor: [
                            "#4CAF50",
                            "#2196F3",
                            "#FF9800",
                            "#9C27B0"
                        ]
                    }]
                },
                options: {
                    responsive: true
                }
            });
        });
        </script>';

        return $chart;
    }

    /**
     * Get performance comparison table
     */
    protected function getPerformanceTable()
    {
        $topPerforming = NotificationModel::where('status', 'sent')
            ->whereNotNull('recipients')
            ->where('recipients', '>', 0)
            ->orderByRaw('(click_count / recipients) DESC')
            ->limit(10)
            ->get();

        $tableContent = '
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Notification</th>
                    <th>Type</th>
                    <th>Delivered</th>
                    <th>Clicks</th>
                    <th>Click Rate</th>
                    <th>Sent At</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($topPerforming as $notification) {
            $clickRate = $notification->recipients > 0
                ? round(($notification->click_count / $notification->recipients) * 100, 1)
                : 0;

            $tableContent .= '
                <tr>
                    <td><strong>' . htmlspecialchars($notification->title) . '</strong></td>
                    <td><span class="label label-primary">' . ucfirst($notification->type) . '</span></td>
                    <td>' . number_format($notification->recipients) . '</td>
                    <td>' . number_format($notification->click_count ?? 0) . '</td>
                    <td><strong>' . $clickRate . '%</strong></td>
                    <td>' . $notification->sent_at->format('M d, Y H:i') . '</td>
                </tr>';
        }

        $tableContent .= '
            </tbody>
        </table>';

        return new Box('Top Performing Notifications', $tableContent);
    }

    /**
     * Enhanced connection test with detailed stats
     */
    public function testConnection()
    {
        try {
            $oneSignal = new OneSignalService();
            $stats = $oneSignal->getAppStats();

            if ($stats['success']) {
                admin_success(
                    '🟢 OneSignal Connected Successfully!',
                    'App Stats: ' . $stats['total_users'] . ' total, ' . $stats['messageable_users'] . ' messageable users'
                );

                // Also test device sync
                $deviceCount = OneSignalDevice::count();
                admin_info('📱 Device Database Status', $deviceCount . ' devices registered locally');
            } else {
                admin_error('🔴 OneSignal Connection Failed', $stats['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            admin_error('🔴 Connection Test Failed', $e->getMessage());
        }

        return back();
    }

    /**
     * Sync devices from OneSignal
     */
    public function syncDevices()
    {
        try {
            $oneSignal = new OneSignalService();

            // For now, return a placeholder result
            // This method would need to be implemented in OneSignalService
            $result = ['success' => true, 'synced' => 0, 'new' => 0, 'error' => 'Method not yet implemented'];

            admin_info('ℹ️ Device Sync', 'This feature is coming soon. Please use the API endpoints for now.');
        } catch (\Exception $e) {
            admin_error('❌ Sync Error', $e->getMessage());
        }

        return back();
    }

    /**
     * Send test notification to specific device
     */
    public function sendTestNotification(Request $request)
    {
        $request->validate([
            'player_id' => 'required|string',
        ]);

        try {
            $oneSignal = new OneSignalService();

            // For now, use the existing send method with specific targeting
            // This would need a specific sendToPlayer method in OneSignalService
            admin_info('🧪 Test Notification', 'Test notification feature is coming soon. Use the quick send for now.');
        } catch (\Exception $e) {
            admin_error('❌ Test failed', $e->getMessage());
        }

        return back();
    }
}
