<style>
    /* =======================
   ORDER PAGE STYLES
   ======================= */
    .order-class-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #1e293b;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .order-class-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        position: relative;
        overflow: hidden;
    }

    .order-class-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: order-class-pulse 8s ease-in-out infinite;
    }

    @keyframes order-class-pulse {

        0%,
        100% {
            transform: scale(1) rotate(0deg);
        }

        50% {
            transform: scale(1.1) rotate(180deg);
        }
    }

    .order-class-header-content {
        position: relative;
        z-index: 2;
    }

    .order-class-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .order-class-header-meta {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .order-class-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .order-class-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .order-class-stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .order-class-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .order-class-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .order-class-stat-card.success::before {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    }

    .order-class-stat-card.info::before {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    }

    .order-class-stat-card.warning::before {
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
    }

    .order-class-stat-card.primary::before {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .order-class-stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #1e293b;
    }

    .order-class-stat-label {
        font-size: 0.85rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .order-class-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }

    .order-class-section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .order-class-section-header i {
        font-size: 1.25rem;
        color: #667eea;
    }

    .order-class-section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .order-class-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .order-class-info-field {
        margin-bottom: 1.25rem;
    }

    .order-class-field-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    .order-class-field-value {
        font-size: 0.95rem;
        color: #1e293b;
        font-weight: 500;
    }

    .order-class-field-value a {
        color: #667eea;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .order-class-field-value a:hover {
        color: #5a67d8;
        text-decoration: underline;
    }

    .order-class-table-container {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        overflow-x: auto;
    }

    .order-class-table {
        width: 100%;
        border-collapse: collapse;
    }

    .order-class-table thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        padding: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
    }

    .order-class-table tbody td {
        padding: 1.25rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .order-class-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .order-class-table tbody tr:hover {
        background: rgba(102, 126, 234, 0.03);
    }

    .order-class-product-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #f7fafc;
    }

    .order-class-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.8rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .order-class-badge.success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
    }

    .order-class-badge.warning {
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        color: white;
    }

    .order-class-badge.info {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        color: white;
    }

    .order-class-badge.secondary {
        background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
        color: white;
    }

    .order-class-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 0.9rem;
        width: 100%;
        justify-content: center;
    }

    .order-class-btn:hover {
        transform: translateY(-2px);
        text-decoration: none;
    }

    .order-class-btn.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .order-class-btn.primary:hover {
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }

    .order-class-btn.success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
    }

    .order-class-btn.success:hover {
        box-shadow: 0 8px 20px rgba(72, 187, 120, 0.4);
    }

    .order-class-btn.outline {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        box-shadow: 0 2px 6px rgba(102, 126, 234, 0.1);
    }

    .order-class-btn.outline:hover {
        background: #667eea;
        color: white;
    }

    .order-class-timeline {
        position: relative;
        padding-left: 1.5rem;
    }

    .order-class-timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }

    .order-class-timeline-item {
        position: relative;
        padding: 1rem 0 1rem 1.5rem;
    }

    .order-class-timeline-item::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 1.5rem;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #cbd5e0;
        border: 3px solid white;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .order-class-timeline-item.active {
        border-left-color: #48bb78;
    }

    .order-class-timeline-item.active::before {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        box-shadow: 0 0 0 4px rgba(72, 187, 120, 0.2);
    }

    .order-class-timeline-title {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .order-class-timeline-date {
        font-size: 0.85rem;
        color: #64748b;
    }

    .order-class-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .order-class-notes {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        border-left: 4px solid #667eea;
        padding: 1rem 1.25rem;
        border-radius: 0 8px 8px 0;
        margin-top: 1rem;
    }

    .order-class-empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #64748b;
    }

    .order-class-empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #cbd5e0;
    }

    .order-class-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }

    .order-class-table-footer {
        background: #f8fafc;
        font-weight: 600;
    }

    .order-class-table-footer td {
        padding: 1rem;
        border-top: 2px solid #e2e8f0;
    }

    .order-class-table-footer .total {
        background: #f1f5f9;
        font-size: 1.1rem;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .order-class-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .order-class-container {
            padding: 1rem;
        }

        .order-class-header {
            padding: 1.5rem;
        }

        .order-class-header h1 {
            font-size: 1.5rem;
        }

        .order-class-header-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .order-class-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .order-class-info-grid {
            grid-template-columns: 1fr;
        }

        .order-class-table thead {
            display: none;
        }

        .order-class-table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }

        .order-class-table tbody td {
            display: block;
            text-align: left;
            padding: 0.5rem 0;
            border: none;
        }

        .order-class-table tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            display: block;
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
    }

    @media (max-width: 480px) {
        .order-class-stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="order-class-container">
    <!-- Header Section -->
    <div class="order-class-header">
        <div class="order-class-header-content">
            <h1>
                <i class="bi bi-receipt"></i>
                Order #{{ $order->id ?? 'Unknown' }}
            </h1>
            <div class="order-class-header-meta">
                <div>
                    <i class="bi bi-calendar-event"></i>
                    Created:
                    {{ $order->created_at ? $order->created_at->format('M d, Y \a\t H:i') : 'Date not available' }}
                </div>
                <div class="order-class-status-badge">
                    @php
                        $statusInfo =
                            isset($orderStatuses) && isset($order->order_state)
                                ? $orderStatuses[$order->order_state] ?? [
                                        'text' => 'Unknown',
                                        'class' => 'secondary',
                                        'icon' => 'question-circle',
                                    ]
                                : ['text' => 'Unknown', 'class' => 'secondary', 'icon' => 'question-circle'];
                    @endphp
                    <i class="bi bi-{{ $statusInfo['icon'] }}"></i>
                    {{ $statusInfo['text'] }}
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="order-class-stats-grid">
        <div class="order-class-stat-card success">
            <div class="order-class-stat-number">{{ $totalItems ?? 0 }}</div>
            <div class="order-class-stat-label">Total Items</div>
        </div>
        <div class="order-class-stat-card info">
            <div class="order-class-stat-number">{{ $totalQuantity ?? 0 }}</div>
            <div class="order-class-stat-label">Total Quantity</div>
        </div>
        <div class="order-class-stat-card warning">
            <div class="order-class-stat-number">UGX {{ number_format($order->order_total ?? 0) }}</div>
            <div class="order-class-stat-label">Order Total</div>
        </div>
        <div class="order-class-stat-card primary">
            <div class="order-class-stat-number">UGX
                {{ number_format($order->payable_amount ?? ($order->order_total ?? 0)) }}</div>
            <div class="order-class-stat-label">Final Amount</div>
        </div>
    </div>

    <div class="order-class-row">
        <!-- Left Column -->
        <div>
            <!-- Customer Information -->
            <div class="order-class-card">
                <div class="order-class-section-header">
                    <i class="bi bi-person-circle"></i>
                    <h2 class="order-class-section-title">Customer Information</h2>
                </div>

                <div class="order-class-info-grid">
                    <div>
                        <div class="order-class-info-field">
                            <div class="order-class-field-label">Customer Name</div>
                            <div class="order-class-field-value">{{ $order->customer_name ?: 'N/A' }}</div>
                        </div>
                        <div class="order-class-info-field">
                            <div class="order-class-field-label">Email Address</div>
                            <div class="order-class-field-value">
                                @if ($order->mail ?? null)
                                    <a href="mailto:{{ $order->mail }}">
                                        <i class="bi bi-envelope"></i> {{ $order->mail }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="order-class-info-field">
                            <div class="order-class-field-label">Primary Phone</div>
                            <div class="order-class-field-value">
                                @if ($order->customer_phone_number_1 ?? null)
                                    <a href="tel:{{ $order->customer_phone_number_1 }}">
                                        <i class="bi bi-telephone"></i> {{ $order->customer_phone_number_1 }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
                        @if ($order->customer_phone_number_2 ?? null)
                            <div class="order-class-info-field">
                                <div class="order-class-field-label">Secondary Phone</div>
                                <div class="order-class-field-value">
                                    <a href="tel:{{ $order->customer_phone_number_2 }}">
                                        <i class="bi bi-telephone"></i> {{ $order->customer_phone_number_2 }}
                                    </a>
                                </div>
                            </div>
                        @endif
                        <div class="order-class-info-field">
                            <div class="order-class-field-label">Customer Address</div>
                            <div class="order-class-field-value">{{ $order->customer_address ?: 'N/A' }}</div>
                        </div>
                        @if (isset($customer) && $customer)
                            <div class="order-class-info-field">
                                <div class="order-class-field-label">Registered User</div>
                                <div class="order-class-field-value">
                                    <span class="order-class-badge success">
                                        <i class="bi bi-person-check"></i> {{ $customer->name }} (ID:
                                        {{ $customer->id }})
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Additional Order Details (from JSON) -->
            @php
                $orderDetailsData = null;
                try {
                    $orderDetailsData = json_decode($order->order_details, true);
                } catch (\Exception $e) {
                    $orderDetailsData = null;
                }

                // Define fields to display with nice labels
                $fieldsToDisplay = [
                    'customer_name' => ['label' => 'Customer Name', 'icon' => 'person'],
                    'mail' => ['label' => 'Email Address', 'icon' => 'envelope', 'type' => 'email'],
                    'customer_phone_number_1' => ['label' => 'Phone Number 1', 'icon' => 'telephone', 'type' => 'phone'],
                    'customer_phone_number_2' => ['label' => 'Phone Number 2', 'icon' => 'telephone', 'type' => 'phone'],
                    'phone_number' => ['label' => 'Contact Phone', 'icon' => 'telephone', 'type' => 'phone'],
                    'phone_number_1' => ['label' => 'Alt Phone 1', 'icon' => 'telephone', 'type' => 'phone'],
                    'phone_number_2' => ['label' => 'Alt Phone 2', 'icon' => 'telephone', 'type' => 'phone'],
                    'customer_address' => ['label' => 'Customer Address', 'icon' => 'geo-alt'],
                    'delivery_method' => ['label' => 'Delivery Method', 'icon' => 'truck', 'type' => 'badge'],
                    'delivery_address_text' => ['label' => 'Delivery Location', 'icon' => 'pin-map'],
                    'delivery_address_details' => ['label' => 'Delivery Details', 'icon' => 'info-circle'],
                    'delivery_district' => ['label' => 'Delivery District', 'icon' => 'map'],
                    'delivery_amount' => ['label' => 'Delivery Fee', 'icon' => 'cash', 'type' => 'currency'],
                    'payable_amount' => ['label' => 'Total Payable', 'icon' => 'currency-dollar', 'type' => 'currency'],
                    'order_total' => ['label' => 'Order Total', 'icon' => 'calculator', 'type' => 'currency'],
                    'payment_confirmation' => ['label' => 'Payment Status', 'icon' => 'check-circle'],
                    'description' => ['label' => 'Order Description', 'icon' => 'card-text'],
                ];

                // Filter out empty values and already displayed values
                $extraDetails = [];
                if ($orderDetailsData && is_array($orderDetailsData)) {
                    foreach ($fieldsToDisplay as $key => $info) {
                        if (isset($orderDetailsData[$key]) && 
                            !empty($orderDetailsData[$key]) && 
                            $orderDetailsData[$key] !== '' && 
                            $orderDetailsData[$key] !== '0' && 
                            $orderDetailsData[$key] !== 0 &&
                            $orderDetailsData[$key] !== '0.00') {
                            $extraDetails[$key] = [
                                'label' => $info['label'],
                                'value' => $orderDetailsData[$key],
                                'icon' => $info['icon'],
                                'type' => $info['type'] ?? 'text'
                            ];
                        }
                    }
                }
            @endphp

            @if(count($extraDetails) > 0)
            <div class="order-class-card">
                <div class="order-class-section-header">
                    <i class="bi bi-info-circle-fill"></i>
                    <h2 class="order-class-section-title">Additional Order Details</h2>
                </div>

                <div class="order-class-info-grid">
                    @foreach($extraDetails as $key => $detail)
                        <div class="order-class-info-field">
                            <div class="order-class-field-label">
                                <i class="bi bi-{{ $detail['icon'] }}"></i> {{ $detail['label'] }}
                            </div>
                            <div class="order-class-field-value">
                                @if($detail['type'] === 'currency')
                                    <strong style="color: #667eea;">UGX {{ number_format((float)$detail['value'], 0) }}</strong>
                                @elseif($detail['type'] === 'email')
                                    <a href="mailto:{{ $detail['value'] }}">
                                        <i class="bi bi-envelope"></i> {{ $detail['value'] }}
                                    </a>
                                @elseif($detail['type'] === 'phone')
                                    <a href="tel:{{ $detail['value'] }}">
                                        <i class="bi bi-telephone"></i> {{ $detail['value'] }}
                                    </a>
                                @elseif($detail['type'] === 'badge')
                                    <span class="order-class-badge info">
                                        <i class="bi bi-truck"></i> {{ ucfirst($detail['value']) }}
                                    </span>
                                @else
                                    {{ $detail['value'] }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(isset($orderDetailsData['pay_on_delivery']) && $orderDetailsData['pay_on_delivery'])
                <div style="margin-top: 1rem; padding: 1rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <i class="bi bi-cash-coin" style="color: #d97706;"></i>
                    <strong style="color: #92400e;">Pay on Delivery</strong> - Customer will pay cash upon receiving the order
                </div>
                @endif
            </div>
            @endif

            <!-- Order Items -->
            <div class="order-class-card">
                <div class="order-class-section-header">
                    <i class="bi bi-bag-check"></i>
                    <h2 class="order-class-section-title">Order Items</h2>
                </div>

                @if (isset($orderItems) && count($orderItems) > 0)
                    <div class="order-class-table-container">
                        <table class="order-class-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Details</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Variants</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orderItems as $item)
                                    <tr>
                                        <td data-label="Product">
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                @if (isset($item->pro) && $item->pro && isset($item->pro->feature_photo))
                                                    <img src="{{ asset('storage/' . $item->pro->feature_photo) }}"
                                                        alt="Product Image" class="order-class-product-img"
                                                        onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRjhGOUZBIi8+CjxwYXRoIGQ9Ik0yMCAyMEg0MFY0MEgyMFYyMFoiIGZpbGw9IiNFNUU3RUIiLz4KPC9zdmc+'">
                                                @else
                                                    <div class="order-class-product-img"
                                                        style="background: #f1f5f9; display: flex; align-items: center; justify-content: center;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <strong>{{ isset($item->pro) && $item->pro ? $item->pro->name : 'Product Not Found' }}</strong>
                                                    <br>
                                                    <small class="text-muted">ID: {{ $item->product ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Details">
                                            @if (isset($item->pro) && $item->pro)
                                                <small class="text-muted d-block">SKU:
                                                    {{ $item->pro->id ?? 'N/A' }}</small>
                                                <small class="text-muted d-block">Price: UGX
                                                    {{ number_format($item->pro->price_1 ?? 0) }}</small>
                                            @endif
                                        </td>
                                        <td data-label="Quantity">
                                            <span class="order-class-badge info">{{ $item->qty ?? 0 }}</span>
                                        </td>
                                        <td data-label="Unit Price">
                                            UGX
                                            {{ number_format(isset($item->pro) && $item->pro ? $item->pro->price_1 ?? 0 : 0) }}
                                        </td>
                                        <td data-label="Total" class="fw-bold">
                                            UGX {{ number_format($item->amount ?? 0) }}
                                        </td>
                                        <td data-label="Variants">
                                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                                @if (isset($item->color) && $item->color)
                                                    <span class="order-class-badge info">{{ $item->color }}</span>
                                                @endif
                                                @if (isset($item->size) && $item->size)
                                                    <span
                                                        class="order-class-badge secondary">{{ $item->size }}</span>
                                                @endif
                                                @if ((!isset($item->color) || !$item->color) && (!isset($item->size) || !$item->size))
                                                    <span class="text-muted">No variants</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="order-class-table-footer">
                                <tr>
                                    <td colspan="4">Subtotal:</td>
                                    <td>UGX
                                        {{ number_format(($order->order_total ?? 0) - ($order->delivery_amount ?? 0)) }}
                                    </td>
                                    <td></td>
                                </tr>
                                @if (($order->delivery_amount ?? 0) > 0)
                                    <tr>
                                        <td colspan="4">Delivery Fee:</td>
                                        <td>UGX {{ number_format($order->delivery_amount) }}</td>
                                        <td></td>
                                    </tr>
                                @endif
                                <tr class="total">
                                    <td colspan="4">Grand Total:</td>
                                    <td>UGX {{ number_format($order->payable_amount ?? ($order->order_total ?? 0)) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="order-class-empty-state">
                        <i class="bi bi-cart-x"></i>
                        <p>No items found for this order</p>
                    </div>
                @endif
            </div>

            <!-- Delivery Information -->
            <div class="order-class-card">
                <div class="order-class-section-header">
                    <i class="bi bi-truck"></i>
                    <h2 class="order-class-section-title">Delivery Information</h2>
                </div>

                <div class="order-class-info-grid">
                    <div>
                        <div class="order-class-info-field">
                            <div class="order-class-field-label">Delivery Method</div>
                            <div class="order-class-field-value">{{ $order->delivery_method ?: 'Standard Delivery' }}
                            </div>
                        </div>
                        <div class="order-class-info-field">
                            <div class="order-class-field-label">Delivery District</div>
                            <div class="order-class-field-value">{{ $order->delivery_district ?: 'N/A' }}</div>
                        </div>
                    </div>
                    <div>
                        <div class="order-class-info-field">
                            <div class="order-class-field-label">Delivery Address</div>
                            <div class="order-class-field-value">
                                {{ $order->delivery_address_details ?: $order->delivery_address_text ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="order-class-info-field">
                            <div class="order-class-field-label">Delivery Fee</div>
                            <div class="order-class-field-value">
                                <span class="order-class-badge warning">
                                    UGX {{ number_format($order->delivery_amount ?? 0) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @if (isset($deliveryAddress) && $deliveryAddress)
                    <div class="order-class-notes">
                        <h6><i class="bi bi-info-circle"></i> Saved Delivery Address</h6>
                        <p class="mb-0">{{ $deliveryAddress->address }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <!-- Action Buttons -->
            <div class="order-class-card" style="position: sticky; top: 1rem;">
                <div class="order-class-section-header">
                    <i class="bi bi-gear"></i>
                    <h2 class="order-class-section-title">Quick Actions</h2>
                </div>

                <div class="order-class-actions">
                    <a href="{{ admin_url('orders/' . ($order->id ?? '0') . '/edit') }}"
                        class="order-class-btn primary">
                        <i class="bi bi-pencil-square"></i> Update Order Status
                    </a>
                    <button class="order-class-btn success" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Order Details
                    </button>
                    <a href="{{ admin_url('orders') }}" class="order-class-btn outline">
                        <i class="bi bi-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="order-class-card">
                <div class="order-class-section-header">
                    <i class="bi bi-credit-card"></i>
                    <h2 class="order-class-section-title">Payment Information</h2>
                </div>

                <div class="order-class-info-field">
                    <div class="order-class-field-label">Payment Status</div>
                    <div class="order-class-field-value">
                        @if (($order->payment_confirmation ?? null) || ($order->stripe_paid ?? null) == 'Yes')
                            <span class="order-class-badge success">
                                <i class="bi bi-check-circle-fill"></i> Payment Completed
                            </span>
                        @else
                            <span class="order-class-badge warning">
                                <i class="bi bi-clock-fill"></i> Payment Pending
                            </span>
                        @endif
                    </div>
                </div>

                @if (isset($order->payment_gateway) && $order->payment_gateway)
                    <div class="order-class-info-field">
                        <div class="order-class-field-label">Payment Gateway</div>
                        <div class="order-class-field-value">
                            {{ isset($paymentGateways) && isset($paymentGateways[$order->payment_gateway]) ? $paymentGateways[$order->payment_gateway] : ucfirst($order->payment_gateway) }}
                        </div>
                    </div>
                @endif

                @if (isset($order->stripe_id) && $order->stripe_id)
                    <div class="order-class-info-field">
                        <div class="order-class-field-label">Stripe Payment ID</div>
                        <div class="order-class-field-value">{{ $order->stripe_id }}</div>
                    </div>
                @endif

                @if (isset($order->stripe_url) && $order->stripe_url)
                    <div class="order-class-info-field">
                        <a href="{{ $order->stripe_url }}" target="_blank" class="order-class-btn outline"
                            style="width: auto;">
                            <i class="bi bi-link-45deg"></i> View Stripe Payment
                        </a>
                    </div>
                @endif

                @if (isset($order->pesapal_order_tracking_id) && $order->pesapal_order_tracking_id)
                    <div class="order-class-info-field">
                        <div class="order-class-field-label">PesaPal Tracking ID</div>
                        <div class="order-class-field-value">{{ $order->pesapal_order_tracking_id }}</div>
                    </div>
                @endif

                @if (isset($order->pesapal_status) && $order->pesapal_status)
                    <div class="order-class-info-field">
                        <div class="order-class-field-label">PesaPal Status</div>
                        <div class="order-class-field-value">
                            <span class="order-class-badge info">{{ $order->pesapal_status }}</span>
                        </div>
                    </div>
                @endif

                @if (isset($order->pay_on_delivery) && $order->pay_on_delivery)
                    <div class="order-class-notes">
                        <i class="bi bi-cash-coin"></i> Pay on Delivery
                    </div>
                @endif
            </div>

            <!-- Order Timeline -->
            <div class="order-class-card">
                <div class="order-class-section-header">
                    <i class="bi bi-clock-history"></i>
                    <h2 class="order-class-section-title">Order Timeline</h2>
                </div>

                <div class="order-class-timeline">
                    <div class="order-class-timeline-item {{ $order->created_at ?? null ? 'active' : '' }}">
                        <div class="order-class-timeline-title">Order Placed</div>
                        <div class="order-class-timeline-date">
                            {{ $order->created_at ? $order->created_at->format('M d, Y \a\t H:i') : 'N/A' }}
                        </div>
                    </div>

                    @if (isset($order->payment_completed_at) && $order->payment_completed_at)
                        <div class="order-class-timeline-item active">
                            <div class="order-class-timeline-title">Payment Completed</div>
                            <div class="order-class-timeline-date">{{ $order->payment_completed_at }}</div>
                        </div>
                    @endif

                    <div class="order-class-timeline-item {{ ($order->order_state ?? 0) >= 1 ? 'active' : '' }}">
                        <div class="order-class-timeline-title">Processing Started</div>
                        <div class="order-class-timeline-date">
                            @if (($order->order_state ?? 0) >= 1)
                                {{ $order->updated_at ? $order->updated_at->format('M d, Y \a\t H:i') : 'Date not available' }}
                            @else
                                Pending
                            @endif
                        </div>
                    </div>

                    <div class="order-class-timeline-item {{ ($order->order_state ?? 0) >= 2 ? 'active' : '' }}">
                        <div class="order-class-timeline-title">Order Completed</div>
                        <div class="order-class-timeline-date">
                            @if (($order->order_state ?? 0) >= 2)
                                {{ $order->updated_at ? $order->updated_at->format('M d, Y \a\t H:i') : 'Date not available' }}
                            @else
                                Pending
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <!-- Order Notes -->
    @if (isset($order->description) && $order->description)
        <div class="order-class-card">
            <div class="order-class-section-header">
                <i class="bi bi-sticky"></i>
                <h2 class="order-class-section-title">Order Notes</h2>
            </div>
            <div class="order-class-notes">
                <i class="bi bi-chat-quote"></i>
                {{ $order->description }}
            </div>
        </div>
    @endif
</div>
