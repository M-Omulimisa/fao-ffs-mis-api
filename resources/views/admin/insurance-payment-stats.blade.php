<style>
    .payment-stats-container {
        background: #fff;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 3px;
        box-shadow: 0 1px 1px rgba(0,0,0,.05);
    }
    .payment-stat-box {
        display: inline-block;
        padding: 15px 20px;
        margin-right: 15px;
        border-radius: 3px;
        min-width: 180px;
        text-align: center;
    }
    .payment-stat-box.total {
        background: #3c8dbc;
        color: white;
    }
    .payment-stat-box.paid {
        background: #00a65a;
        color: white;
    }
    .payment-stat-box.pending {
        background: #f39c12;
        color: white;
    }
    .payment-stat-box.overdue {
        background: #dd4b39;
        color: white;
    }
    .payment-stat-box h4 {
        margin: 0 0 5px 0;
        font-size: 24px;
        font-weight: bold;
    }
    .payment-stat-box p {
        margin: 0;
        font-size: 12px;
        opacity: 0.9;
    }
</style>

<div class="payment-stats-container">
    <h4 style="margin-top: 0; margin-bottom: 15px;">
        <i class="fa fa-bar-chart"></i> Payment Statistics Overview
    </h4>
    
    <div class="payment-stat-box total">
        <h4>UGX {{ number_format($total, 0) }}</h4>
        <p>Total Expected</p>
    </div>
    
    <div class="payment-stat-box paid">
        <h4>UGX {{ number_format($paid, 0) }}</h4>
        <p>Total Paid</p>
    </div>
    
    <div class="payment-stat-box pending">
        <h4>UGX {{ number_format($pending, 0) }}</h4>
        <p>Pending Payments</p>
    </div>
    
    <div class="payment-stat-box overdue">
        <h4>UGX {{ number_format($overdue, 0) }}</h4>
        <p>Overdue ({{ $overdueCount }} payments)</p>
    </div>
</div>
