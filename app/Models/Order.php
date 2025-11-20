<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stripe\Customer;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'receipt_number',
        'invoice_number',
        'order_date',
        'order_state',
        'amount',
        'payment_confirmation',
        'mail',
        'delivery_district',
        'description',
        'notes',
        'customer_name',
        'customer_phone_number_1',
        'customer_phone_number_2',
        'customer_address',
        'order_total',
        'sub_total',
        'tax',
        'discount',
        'order_details',
        'delivery_method',
        'delivery_address_id',
        'delivery_address_text',
        'delivery_address_details',
        'delivery_amount',
        'payable_amount',
        'items',
        'phone_number_2',
        'phone_number_1',
        'phone_number',
        'user', // Customer ID
        // Pesapal fields
        'payment_gateway',
        'pesapal_order_tracking_id',
        'pesapal_merchant_reference',
        'pesapal_status',
        'pesapal_payment_method',
        'pesapal_redirect_url',
        'payment_status',
        'payment_completed_at',
        'pay_on_delivery',
        // Commission fields
        'order_is_paid',
        'order_paid_date',
        'order_paid_amount',
        'has_detehm_seller',
        'dtehm_seller_id',
        'dtehm_user_id',
        // Email tracking fields
        'pending_mail_sent',
        'processing_mail_sent',
        'completed_mail_sent',
        'canceled_mail_sent',
        'failed_mail_sent'
    ];

    //boot
    public static function boot()
    {
        parent::boot();
        //created
        self::created(function ($m) {});

        //updated
        self::updated(function ($m) {});


        self::deleting(function ($m) {
            try {
                $items = OrderedItem::where('order', $m->id)->get();
                foreach ($items as $item) {
                    $item->delete();
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
    }


    public static function send_mails($m)
    {
        try {
            Log::info("Checking emails for order {$m->id} with state {$m->order_state}");

            $customer = User::find($m->user);
            if ($customer == null) {
                Log::warning('Order email: Customer not found for order ID: ' . $m->id);
                return;
            }

            // Check what emails need to be sent based on order state and sent status
            $emailType = self::getEmailTypeToSend($m);
            if (!$emailType) {
                Log::info("No email needed for order {$m->id} with state {$m->order_state} - all emails already sent");
                return;
            }

            Log::info("Sending {$emailType} email for order {$m->id}");

            // Generate email content based on order state
            $emailContent = self::generateEmailContent($m, $customer, $emailType);

            if (!$emailContent) {
                Log::warning('No email content generated for order ' . $m->id . ' type ' . $emailType);
                return;
            }

            // Send customer email
            if (!empty($customer->email) && filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $customerData = [
                        'body' => $emailContent['customer'],
                        'data' => $emailContent['customer'],
                        'name' => $customer->first_name . ' ' . $customer->last_name,
                        'email' => $customer->email,
                        'subject' => $emailContent['subject'],
                        'view' => 'mail-1'
                    ];

                    set_time_limit(30);
                    Utils::mail_sender($customerData);

                    // Mark email as sent using direct DB update to avoid triggering hooks
                    self::markEmailAsSent($m->id, $emailType);

                    Log::info("Order {$emailType} email sent successfully to customer {$customer->email} for order {$m->id}");
                } catch (\Throwable $th) {
                    Log::error("Failed to send {$emailType} customer email for order {$m->id}: " . $th->getMessage());
                }
            } else {
                Log::warning("Invalid or missing customer email for order {$m->id}. Email: " . ($customer->email ?? 'NULL'));
            }

            // Send admin notification only for new orders (pending state)
            if ($emailType === 'pending') {
                $adminEmails = [
                    'mubahood360@gmail.com',
                    'blitxpressonline@gmail.com',
                ];

                foreach ($adminEmails as $adminEmail) {
                    try {
                        $adminData = [
                            'body' => $emailContent['admin'],
                            'data' => $emailContent['admin'],
                            'name' => 'BlitXpress Admin',
                            'email' => $adminEmail,
                            'subject' => $emailContent['admin_subject'],
                            'view' => 'mail-1'
                        ];

                        set_time_limit(30);
                        Utils::mail_sender($adminData);
                        Log::info("Admin notification sent to {$adminEmail} for order {$m->id}");
                    } catch (\Throwable $th) {
                        Log::error('Failed to send admin email for order ' . $m->id . ' to ' . $adminEmail . ': ' . $th->getMessage());
                    }
                }
            }
        } catch (\Throwable $th) {
            Log::error('Critical error in send_mails for order ' . $m->id . ': ' . $th->getMessage());
        }
    }

    /**
     * Determine what type of email needs to be sent based on order state and sent status
     */
    private static function getEmailTypeToSend($order)
    {
        $stateMap = [
            0 => 'pending',
            1 => 'processing',
            2 => 'completed',
            3 => 'canceled',
            4 => 'failed'
        ];

        $state = (int)$order->order_state;

        if (!isset($stateMap[$state])) {
            Log::warning("Unknown order state {$state} for order {$order->id}");
            return null;
        }

        $emailType = $stateMap[$state];
        $sentField = $emailType . '_mail_sent';
        $alreadySent = $order->{$sentField} === 'Yes';

        Log::info("Order {$order->id} - State: {$state} ({$emailType}), {$sentField}: " . ($order->{$sentField} ?? 'NULL') . ", Already sent: " . ($alreadySent ? 'Yes' : 'No'));

        return $alreadySent ? null : $emailType;
    }

    /**
     * Generate professional email content based on order state
     */
    private static function generateEmailContent($order, $customer, $emailType)
    {
        $content = [];

        switch ($emailType) {
            case 'pending':
                $content['subject'] = "Order Confirmation #" . $order->id . " - BlitXpress";
                $content['customer'] = self::getPendingEmailContent($order, $customer);
                $content['admin_subject'] = "New Order #" . $order->id . " - BlitXpress";
                $content['admin'] = self::getAdminEmailContent($order, $customer);
                break;

            case 'processing':
                $content['subject'] = "Order #" . $order->id . " is Being Processed - BlitXpress";
                $content['customer'] = self::getProcessingEmailContent($order, $customer);
                break;

            case 'completed':
                $content['subject'] = "Order #" . $order->id . " Delivered - BlitXpress";
                $content['customer'] = self::getCompletedEmailContent($order, $customer);
                break;

            case 'canceled':
                $content['subject'] = "Order #" . $order->id . " Canceled - BlitXpress";
                $content['customer'] = self::getCanceledEmailContent($order, $customer);
                break;

            case 'failed':
                $content['subject'] = "Order #" . $order->id . " Issue - BlitXpress";
                $content['customer'] = self::getFailedEmailContent($order, $customer);
                break;

            default:
                return null;
        }

        return $content;
    }

    /**
     * Mark email as sent using direct DB update to avoid triggering model events
     */
    private static function markEmailAsSent($orderId, $emailType)
    {
        $field = $emailType . '_mail_sent';
        DB::table('orders')->where('id', $orderId)->update([$field => 'Yes']);
    }

    //hasMany ordered items
    public function orderedItems()
    {
        return $this->hasMany(OrderedItem::class, 'order');
    }

    /**
     * Manual method to send any pending emails for all orders
     * This can be used to catch up on missed emails
     */
    public static function sendPendingEmails()
    {
        Log::info('Starting manual email send for all orders with pending emails');

        $orders = self::whereIn('order_state', [0, 1, 2, 3, 4])->get();
        $emailsSent = 0;

        foreach ($orders as $order) {
            $emailType = self::getEmailTypeToSend($order);
            if ($emailType) {
                Log::info("Sending pending {$emailType} email for order {$order->id}");
                self::send_mails($order);
                $emailsSent++;
            }
        }

        Log::info("Manual email send completed. {$emailsSent} emails were sent.");
        return $emailsSent;
    }

    private static function getPendingEmailContent($order, $customer)
    {
        return <<<EOD
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;">Order Confirmation</h2>
    
    <p>Dear {$customer->first_name} {$customer->last_name},</p>
    
    <p>Thank you for your order. We have received your order and it is being reviewed.</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h3 style="margin-top: 0; color: #333;">Order Details</h3>
        <p><strong>Order ID:</strong> #{$order->id}</p>
        <p><strong>Status:</strong> Pending Review</p>
        <p><strong>Order Date:</strong> {$order->created_at->format('M d, Y')}</p>
    </div>
    
    <p>We will contact you shortly to confirm your order details and arrange delivery.</p>
    
    <p>If you have any questions, please contact us at:</p>
    <p>Phone: +256800200146<br>Email: blitexpress@gmail.com</p>
    
    <p>Best regards,<br>BlitXpress Team</p>
</div>
EOD;
    }

    private static function getProcessingEmailContent($order, $customer)
    {
        return <<<EOD
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;">Order Update</h2>
    
    <p>Dear {$customer->first_name} {$customer->last_name},</p>
    
    <p>Good news! Your order is now being processed and prepared for delivery.</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h3 style="margin-top: 0; color: #333;">Order Details</h3>
        <p><strong>Order ID:</strong> #{$order->id}</p>
        <p><strong>Status:</strong> Being Processed</p>
        <p><strong>Expected Delivery:</strong> 1-2 business days</p>
    </div>
    
    <p>We will notify you when your order is ready for delivery.</p>
    
    <p>If you have any questions, please contact us at:</p>
    <p>Phone: +256800200146<br>Email: blitexpress@gmail.com</p>
    
    <p>Best regards,<br>BlitXpress Team</p>
</div>
EOD;
    }

    private static function getCompletedEmailContent($order, $customer)
    {
        return <<<EOD
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;">Order Delivered</h2>
    
    <p>Dear {$customer->first_name} {$customer->last_name},</p>
    
    <p>Your order has been successfully delivered. We hope you are satisfied with your purchase.</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h3 style="margin-top: 0; color: #333;">Order Details</h3>
        <p><strong>Order ID:</strong> #{$order->id}</p>
        <p><strong>Status:</strong> Delivered</p>
        <p><strong>Delivery Date:</strong> {$order->updated_at->format('M d, Y')}</p>
    </div>
    
    <p>Thank you for choosing BlitXpress. We appreciate your business and look forward to serving you again.</p>
    
    <p>If you have any feedback or questions, please contact us at:</p>
    <p>Phone: +256800200146<br>Email: blitexpress@gmail.com</p>
    
    <p>Best regards,<br>BlitXpress Team</p>
</div>
EOD;
    }

    private static function getCanceledEmailContent($order, $customer)
    {
        return <<<EOD
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;">Order Canceled</h2>
    
    <p>Dear {$customer->first_name} {$customer->last_name},</p>
    
    <p>We regret to inform you that your order has been canceled.</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h3 style="margin-top: 0; color: #333;">Order Details</h3>
        <p><strong>Order ID:</strong> #{$order->id}</p>
        <p><strong>Status:</strong> Canceled</p>
        <p><strong>Cancellation Date:</strong> {$order->updated_at->format('M d, Y')}</p>
    </div>
    
    <p>If you have any questions about this cancellation or would like to place a new order, please contact us.</p>
    
    <p>Contact us at:</p>
    <p>Phone: +256800200146<br>Email: blitexpress@gmail.com</p>
    
    <p>We apologize for any inconvenience caused.</p>
    
    <p>Best regards,<br>BlitXpress Team</p>
</div>
EOD;
    }

    private static function getFailedEmailContent($order, $customer)
    {
        return <<<EOD
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;">Order Issue</h2>
    
    <p>Dear {$customer->first_name} {$customer->last_name},</p>
    
    <p>We encountered an issue with your order and need to contact you to resolve it.</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h3 style="margin-top: 0; color: #333;">Order Details</h3>
        <p><strong>Order ID:</strong> #{$order->id}</p>
        <p><strong>Status:</strong> Requires Attention</p>
        <p><strong>Date:</strong> {$order->updated_at->format('M d, Y')}</p>
    </div>
    
    <p>Please contact us as soon as possible so we can resolve this issue and proceed with your order.</p>
    
    <p>Contact us at:</p>
    <p>Phone: +256800200146<br>Email: blitexpress@gmail.com</p>
    
    <p>We apologize for any inconvenience and appreciate your patience.</p>
    
    <p>Best regards,<br>BlitXpress Team</p>
</div>
EOD;
    }

    private static function getAdminEmailContent($order, $customer)
    {
        $review_url = admin_url('orders/' . $order->id);
        return <<<EOD
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;">New Order Received</h2>
    
    <p>A new order has been placed and requires review.</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h3 style="margin-top: 0; color: #333;">Order Information</h3>
        <p><strong>Order ID:</strong> #{$order->id}</p>
        <p><strong>Customer:</strong> {$customer->first_name} {$customer->last_name}</p>
        <p><strong>Email:</strong> {$customer->email}</p>
        <p><strong>Phone:</strong> {$customer->phone_number}</p>
        <p><strong>Order Date:</strong> {$order->created_at->format('M d, Y h:i A')}</p>
    </div>

    <p><a href="' . $review_url . '" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Review Order</a></p>

    <p>BlitXpress Admin System</p>
</div>
EOD;
    }


    public function create_payment_link()
    {
        return;
        $stripe = env('STRIPE_KEY');
        if (($this->stripe_id != null) && (strlen($this->stripe_id) > 0)) {
            return;
        }


        $itmes = $this->get_items();
        $line_items = [];
        foreach ($itmes as $key => $item) {
            $pro = Product::find($item->product);
            if ($pro == null) {
                continue;
            }
            if ($pro->stripe_price == null || strlen($pro->stripe_price) < 3) {
                continue;
            }
            $line_items[] = [
                'price' => $pro->stripe_price,
                'quantity' => $item->qty,
            ];
        }
        if (count($line_items) < 1) {
            $this->delete();
            throw new \Exception("No items to create payment link");
            return;
        }
        $isSuccess = false;
        $resp = "";
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_KEY')
        );
        try {
            $resp = $stripe->paymentLinks->create([
                'currency' => 'cad',
                'line_items' => $line_items,
            ]);
            $isSuccess = true;
        } catch (\Throwable $th) {
            $isSuccess = false;
            $resp = $th->getMessage();
        }

        if ($isSuccess) {
            $this->stripe_id = $resp->id;
            $this->stripe_url = $resp->url;
            $this->stripe_paid = 'No';
            $this->save();
        }
    }
    public function get_items()
    {
        $items = [];
        foreach (
            OrderedItem::where([
                'order' => $this->id
            ])->get() as $_item
        ) {
            $pro = Product::find($_item->product);
            if ($pro == null) {
                continue;
            }
            if ($_item->pro == null) {
                continue;
            }
            $_item->product_name = $_item->pro->name;
            $_item->product_feature_photo = $_item->pro->feature_photo;
            $_item->product_price_1 = $_item->pro->price_1;
            $_item->product_quantity = $_item->qty;
            $_item->product_id = $_item->pro->id;
            $items[] = $_item;
        }
        return $items;
    }

    //belongs to customer
    public function customer()
    {
        return $this->belongsTo(User::class, 'user');
    }

    /**
     * Get Pesapal transactions for this order
     */
    public function pesapalTransactions()
    {
        return $this->hasMany(PesapalTransaction::class, 'order_id', 'id');
    }

    /**
     * Get the latest Pesapal transaction for this order
     */
    public function latestPesapalTransaction()
    {
        return $this->hasOne(PesapalTransaction::class, 'order_id', 'id')->latest();
    }

    /**
     * Check if order is paid
     */
    public function isPaid()
    {
        //payment_confirmation
        if ($this->payment_status == 'PAID' || $this->pesapal_status == 'COMPLETED') {
            if ($this->payment_confirmation != 'PAID') {
                $this->payment_confirmation = 'PAID';
                $this->save();
            }
            return true;
        }
        return $this->payment_status === 'PAID' ||
            $this->pesapal_status === 'COMPLETED';
    }

    /**
     * Check if order is pending payment
     */
    public function isPendingPayment()
    {
        return $this->payment_status === 'PENDING_PAYMENT' ||
            (empty($this->payment_status) && empty($this->stripe_paid));
    }

    //get payment link
    public function payment_link()
    {
        if ($this->stripe_url != null && strlen($this->stripe_url) > 5) {
            return $this->stripe_url;
        }

        $stripe = env('STRIPE_KEY');
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_KEY')
        );

        $name = 'Order payment for ' . date('Y-m-d H:i:s') . " " . rand(1, 100000);

        $resp = null;
        try {
            $resp = $stripe->products->create([
                'name' => $name,
                'default_price_data' => [
                    'currency' => 'cad',
                    'unit_amount' => 1 * 100,
                ],
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
        if ($resp == null) {
            throw new \Exception("Error Processing Request", 1);
        }
        if ($resp->default_price == null) {
            throw new \Exception("Error Processing Request", 1);
        }
        $linkResp = null;
        try {
            $linkResp = $stripe->paymentLinks->create([
                'currency' => 'cad',
                'line_items' => [
                    [
                        'price' => $resp->default_price,
                        'quantity' => 1,
                    ]
                ]
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
        if ($linkResp == null) {
            throw new \Exception("Error Processing Request", 1);
        }
    }

    //getter for customer_address
    public function getCustomerPhoneNumber1Attribute($value)
    {
        if ($value == null || strlen($value) < 3) {
            try {
                $this->fill_missing_data();
            } catch (\Throwable $th) {
                //throw $th;
            }
            return 'N/A';
        }
        return $value;
    }

    //getter for customer_name
    public function getCustomerNameAttribute($value)
    {
        if ($value == null || strlen($value) < 3) {
            try {
                $this->fill_missing_data();
            } catch (\Throwable $th) {
                //throw $th;
            }
            return 'N/A';
        }
        return $value;
    } 

    public function fill_missing_data()
    {
        $order_details = null;
        try {
            $order_details = json_decode($this->order_details, true);
        } catch (\Throwable $th) {
            //throw $th;
        }
        if ($order_details == null) {
            return;
        }


        $data = [];
        if ($this->date_created == null && strlen($this->created_at) > 3) {
            $data['date_created'] = $this->created_at;
        }
        if (($this->attributes['customer_phone_number_1'] == null ||
                strlen($this->attributes['customer_phone_number_1']) < 3
            )
            && isset($order_details['phone_number_1']) && strlen($order_details['phone_number_1']) > 3
        ) {
            $data['customer_phone_number_1'] = $order_details['phone_number_1'];
        }
        if (($this->attributes['customer_phone_number_2'] == null ||
                strlen($this->attributes['customer_phone_number_2']) < 3
            )
            && isset($order_details['phone_number_2']) && strlen($order_details['phone_number_2']) > 3
        ) {
            $data['customer_phone_number_2'] = $order_details['phone_number_2'];
        }
        if (($this->attributes['customer_name'] == null ||
                strlen($this->attributes['customer_name']) < 3
            )
            && isset($order_details['customer_name']) && strlen($order_details['customer_name']) > 3
        ) {
            $data['customer_name'] = $order_details['customer_name'];
        }
        if (($this->attributes['customer_address'] == null ||
                strlen($this->attributes['customer_address']) < 3
            )
            && isset($order_details['customer_address']) && strlen($order_details['customer_address']) > 3
        ) {
            $data['customer_address'] = $order_details['customer_address'];
        }

        //delivery_address_id
        if (($this->attributes['delivery_district'] == null ||
                strlen($this->attributes['delivery_district']) < 1
            )
            && isset($order_details['delivery_address_text']) && strlen($order_details['delivery_address_text']) > 1
        ) {
            $data['delivery_district'] = $order_details['delivery_address_text'];
        }

        //customer_address

        if (count($data) < 1) {
            return;
        }
        //update using DB facade to avoid triggering model events
        DB::table('orders')->where('id', $this->id)->update($data);


        /* 
 
    "payment_confirmation" => ""
    "date_updated" => null
    "mail" => "wandukwaamok@gmail.com"
    "delivery_district" => null
    "temporary_id" => 0
    "description" => ""
    "customer_name" => null
    "customer_phone_number_1" => null
    "customer_phone_number_2" => null
    "customer_address" => null
    "order_total" => "15000"
    "order_details" => "{"id":0,"created_at":"","updated_at":"","user":"","order_state":"","amount":"","date_created":"","payment_confirmation":"","date_updated":"","mail":"wandukwaamo ▶"
    "stripe_id" => null
    "stripe_url" => null
    "stripe_paid" => "No"
    "pending_mail_sent" => "Yes"
    "processing_mail_sent" => "Yes"
    "completed_mail_sent" => "No"
    "canceled_mail_sent" => "No"
    "failed_mail_sent" => "No"
    "sub_total" => 0
    "tax" => 0
    "discount" => 0
    "delivery_fee" => 0
    "payment_gateway" => "manual"
    "pesapal_order_tracking_id" => null
    "pesapal_merchant_reference" => null
    "pesapal_status" => null
    "pesapal_payment_method" => null
    "pesapal_redirect_url" => null
    "payment_status" => "PENDING_PAYMENT"
    "pay_on_delivery" => 0
    "payment_completed_at" => null

        */

        /* 
          "id" => 0
  "created_at" => ""
  "updated_at" => ""
  "user" => ""
  "order_state" => ""
  "amount" => ""
  "date_created" => ""
  "payment_confirmation" => ""
  "date_updated" => ""
  "mail" => "wandukwaamok@gmail.com"
  "items" => ""
  "delivery_district" => ""
  "temporary_id" => ""
  "temporary_text" => ""
  "description" => ""
  "customer_name" => "Wasike Wasike"
  "customer_phone_number_1" => "0783877626"
  "customer_phone_number_2" => ""
  "customer_address" => "Lwakhakha "
  "order_total" => ""
  "order_details" => ""
  "stripe_id" => ""
  "stripe_text" => ""
  "stripe_url" => ""
  "stripe_paid" => ""
  "delivery_method" => "delivery"
  "delivery_address_id" => "3"
  "delivery_address_text" => "Mbale (Eastern Region Pickup)"
  "delivery_address_details" => "Lwakhakha "
  "delivery_amount" => "0.00"
  "payable_amount" => "15000.0"
  "pay_on_delivery" => true
  "phone_number_2" => "0783877626"
  "phone_number_1" => "0783877626"
  "phone_number" => "0783877626"
        */

    }
}
