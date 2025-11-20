<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            text-align: center;
            margin-top: 100px;
        }

        .card {
            border: none;
            background-color: #ffffff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .btn-pay {
            background-color: #f39c12;
            border: none;
        }

        .btn-pay:hover {
            background-color: #e67e22;
            font-size: 40px !important;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="col-lg-6 mx-auto">
            <form action="https://www.payfast.co.za/eng/process" method="post" class="card">

                <input type="hidden" name="merchant_id" value="15684865">
                <input type="hidden" name="merchant_key" value="8sgoyacf7tsg8">
                <input type="hidden" name="amount" value="{{ $order->amount }}">
                <input type="hidden" name="email_confirmation" value="0">
                <input type="hidden" name="item_description" value="{{ $order->description }}">
                <input type="hidden" name="item_name" value="Order #{{ $order->id }}">
                <input type="hidden" name="return_url" value="{{ $base_link . '&task=success' }}">
                <input type="hidden" name="cancel_url" value="{{ $base_link . '&task=canceled' }}">
                <input type="hidden" name="notify_url" value="{{ $base_link . '&task=update' }}">
                <input type="hidden" name="name_first" value="{{ $order->customer->first_name }}">
                <input type="hidden" name="name_last" value="{{ $order->customer->last_name }}">
                <input type="hidden" name="email_address" value="{{ $order->customer->email }}">
                <input type="hidden" name="cell_number" value="{{ $order->customer->phone_number }}">
                <input type="hidden" name="m_payment_id" value="{{ $order->id }}">

                <h1>ORDER PAYMENT</h1>
                <hr>
                <p>You are about to pay for your order <b>#{{ $order->id }}</b></p>
                <p>Total amount ZAR <b>{{ number_format($order->amount) }}</b>
                <p>
                    <button type="submit" class="btn btn-lg btn-pay mt-3"
                        style="font-weight: 800;
                    font-size: 35px;
                    ">Pay
                        Now</button>
            </form>
        </div>
    </div>

</body>

</html>
