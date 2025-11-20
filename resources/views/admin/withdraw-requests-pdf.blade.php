<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Pending Withdraw Requests - {{ date('Y-m-d') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            color: #222;
            line-height: 1.3;
        }
        
        .header {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #05179F;
        }
        
        .header h1 {
            color: #05179F;
            font-size: 16px;
            margin-bottom: 3px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        
        .header h2 {
            color: #666;
            font-size: 10px;
            font-weight: normal;
        }
        
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .info-item {
            margin-bottom: 3px;
            font-size: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #05179F;
            display: inline-block;
            width: 90px;
        }
        
        .info-value {
            color: #333;
        }
        
        .summary-box {
            background-color: #05179F;
            color: white;
            padding: 8px;
            margin-bottom: 12px;
            text-align: center;
        }
        
        .summary-box h3 {
            font-size: 11px;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .summary-stats {
            display: table;
            width: 100%;
        }
        
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 3px;
            border-right: 1px solid rgba(255,255,255,0.3);
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 13px;
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
        }
        
        .stat-label {
            font-size: 7.5px;
            opacity: 0.95;
            text-transform: uppercase;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        
        table thead {
            background-color: #05179F;
            color: white;
        }
        
        table thead th {
            padding: 5px 3px;
            text-align: left;
            font-size: 7.5px;
            font-weight: bold;
            border: 1px solid #041075;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        table tbody td {
            padding: 4px 3px;
            border: 1px solid #ddd;
            font-size: 8px;
            vertical-align: middle;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .amount {
            font-weight: bold;
            color: #05179F;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 7px;
            color: #666;
        }
        
        .total-row {
            background-color: #05179F !important;
            color: white !important;
            font-weight: bold;
        }
        
        .total-row td {
            border: 1px solid #041075 !important;
            padding: 6px 3px !important;
            font-size: 9px !important;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @page {
            margin: 20mm 20mm;
            size: A4 portrait;
        }
        
        body {
            padding: 0 5mm;
        }
        
        .col-id { width: 4%; }
        .col-name { width: 16%; }
        .col-dip { width: 9%; }
        .col-phone { width: 11%; }
        .col-amount { width: 13%; }
        .col-balance { width: 13%; }
        .col-method { width: 11%; }
        .col-pay-phone { width: 11%; }
        .col-date { width: 12%; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>PENDING WITHDRAW REQUESTS</h1>
        <h2>Financial Report</h2>
    </div>

    <!-- Document Information -->
    <div class="info-section">
        <div class="info-left">
            <div class="info-item">
                <span class="info-label">Generated Date:</span>
                <span class="info-value">{{ $generatedAt }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Generated By:</span>
                <span class="info-value">{{ $generatedBy }}</span>
            </div>
        </div>
        <div class="info-right">
            <div class="info-item">
                <span class="info-label">Total Requests:</span>
                <span class="info-value">{{ $totalCount }} pending</span>
            </div>
            <div class="info-item">
                <span class="info-label">Document Type:</span>
                <span class="info-value">Financial Report</span>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="summary-box">
        <h3>SUMMARY STATISTICS</h3>
        <div class="summary-stats">
            <div class="stat-item">
                <span class="stat-value">{{ $totalCount }}</span>
                <span class="stat-label">Total Requests</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">UGX {{ number_format($totalAmount, 2) }}</span>
                <span class="stat-label">Total Amount</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">UGX {{ $totalCount > 0 ? number_format($totalAmount / $totalCount, 2) : '0.00' }}</span>
                <span class="stat-label">Average per Request</span>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    @if($requests->count() > 0)
        <table>
            <thead>
                <tr>
                    <th class="col-id text-center">#</th>
                    <th class="col-name">User Name</th>
                    <th class="col-dip text-center">DIP ID</th>
                    <th class="col-phone">Phone</th>
                    <th class="col-amount text-right">Amount</th>
                    <th class="col-balance text-right">Balance</th>
                    <th class="col-method text-center">Method</th>
                    <th class="col-pay-phone text-center">Pay. Phone</th>
                    <th class="col-date text-center">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                <tr>
                    <td class="text-center">{{ $request->id }}</td>
                    <td>{{ Str::limit($request->user->name ?? 'N/A', 25) }}</td>
                    <td class="text-center">{{ $request->user->business_name ?? '-' }}</td>
                    <td>{{ $request->user->phone_number ?? 'N/A' }}</td>
                    <td class="amount text-right">{{ number_format($request->amount, 0) }}</td>
                    <td class="text-right">{{ number_format($request->account_balance_before, 0) }}</td>
                    <td class="text-center" style="font-size: 7px;">{{ $request->payment_method == 'mobile_money' ? 'M.Money' : 'Bank' }}</td>
                    <td class="text-center">{{ $request->payment_phone_number ?? '-' }}</td>
                    <td class="text-center" style="font-size: 7.5px;">{{ \Carbon\Carbon::parse($request->created_at)->format('d/m/Y') }}</td>
                </tr>
                @endforeach
                
                <!-- Total Row -->
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>TOTAL PENDING AMOUNT:</strong></td>
                    <td class="text-right"><strong>UGX {{ number_format($totalAmount, 2) }}</strong></td>
                    <td colspan="4" class="text-center"><strong>{{ $totalCount }} REQUEST(S)</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 30px; background-color: #f8f9fa; border: 1px dashed #dee2e6;">
            <p style="font-size: 11px; color: #6c757d;">No pending withdraw requests found.</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p style="margin-bottom: 2px;"><strong>This is a computer-generated document. No signature is required.</strong></p>
        <p style="margin-bottom: 2px;">Generated: {{ date('l, F j, Y \a\t g:i A') }} | Page 1 of 1</p>
        <p>© {{ date('Y') }} - Confidential & Proprietary</p>
    </div>
</body>
</html>
