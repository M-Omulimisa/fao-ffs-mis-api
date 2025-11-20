<div class="row" style="padding: 15px; background-color: #f9f9f9; border-top: 2px solid #d2d6de; margin: 0;">
    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box bg-aqua">
            <span class="info-box-icon"><i class="fa fa-file-text"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Payments</span>
                <span class="info-box-number">{{ number_format($total) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Completed</span>
                <span class="info-box-number">{{ number_format($completed) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box bg-yellow">
            <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending</span>
                <span class="info-box-number">{{ number_format($pending) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box bg-red">
            <span class="info-box-icon"><i class="fa fa-times-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Failed</span>
                <span class="info-box-number">{{ number_format($failed) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box bg-blue">
            <span class="info-box-icon"><i class="fa fa-money"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Amount</span>
                <span class="info-box-number" style="font-size: 16px;">UGX {{ number_format($total_amount, 0) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 col-xs-12">
        <div class="info-box" style="background-color: #00c0ef;">
            <span class="info-box-icon" style="background: rgba(0,0,0,0.1);"><i class="fa fa-check-square"></i></span>
            <div class="info-box-content">
                <span class="info-box-text" style="color: white;">Completed Amount</span>
                <span class="info-box-number" style="font-size: 16px; color: white;">UGX {{ number_format($completed_amount, 0) }}</span>
            </div>
        </div>
    </div>
</div>

<style>
    .info-box {
        display: block;
        min-height: 90px;
        background: #fff;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-box-icon {
        border-top-left-radius: 2px;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 2px;
        display: block;
        float: left;
        height: 90px;
        width: 90px;
        text-align: center;
        font-size: 45px;
        line-height: 90px;
        background: rgba(0,0,0,0.2);
    }

    .info-box-icon > i {
        color: #fff;
    }

    .info-box-content {
        padding: 5px 10px;
        margin-left: 90px;
    }

    .info-box-text {
        display: block;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .info-box-number {
        display: block;
        font-weight: bold;
        font-size: 18px;
    }

    .bg-aqua {
        background-color: #00c0ef !important;
        color: #fff !important;
    }

    .bg-green {
        background-color: #00a65a !important;
        color: #fff !important;
    }

    .bg-yellow {
        background-color: #f39c12 !important;
        color: #fff !important;
    }

    .bg-red {
        background-color: #dd4b39 !important;
        color: #fff !important;
    }

    .bg-blue {
        background-color: #0073b7 !important;
        color: #fff !important;
    }
</style>
