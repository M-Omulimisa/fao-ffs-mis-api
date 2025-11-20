<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Pesapal Payment Testing Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4338ca;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --info-color: #3b82f6;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-light: #94a3b8;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --border-radius: 0.75rem;
        }

        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-light: #94a3b8;
            --border-color: #475569;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
        }

        /* Header */
        .header {
            background: var(--bg-primary);
            padding: 1.5rem 0;
            box-shadow: var(--shadow-sm);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .theme-toggle {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 0.75rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .theme-toggle:hover {
            background: var(--bg-tertiary);
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: var(--bg-primary);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .stat-icon {
            padding: 0.5rem;
            border-radius: 0.5rem;
            font-size: 1.25rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-change {
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Action Buttons */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .action-section {
            background: var(--bg-primary);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            min-width: 120px;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background: var(--error-color);
            color: white;
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.1);
        }

        /* Input Groups */
        .input-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .input-group .form-input {
            flex: 1;
        }

        .input-group .btn {
            white-space: nowrap;
        }

        /* Recent Transactions */
        .transactions-section {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin: 2rem 0;
            overflow: hidden;
        }

        .transactions-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .transactions-table th {
            background: var(--bg-secondary);
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-completed {
            background: rgb(16 185 129 / 0.1);
            color: var(--success-color);
        }

        .status-pending {
            background: rgb(245 158 11 / 0.1);
            color: var(--warning-color);
        }

        .status-failed {
            background: rgb(239 68 68 / 0.1);
            color: var(--error-color);
        }

        /* Response Display */
        .response-container {
            margin: 1rem 0;
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .response-header {
            background: var(--bg-secondary);
            padding: 0.75rem 1rem;
            font-weight: 500;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Specific styling for response status */
        #response-status {
            font-weight: bold;
            font-size: 0.95rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        #response-status.text-success {
            color: var(--success-color) !important;
            background: rgba(16, 185, 129, 0.1);
        }

        #response-status.text-error {
            color: var(--error-color) !important;
            background: rgba(239, 68, 68, 0.1);
        }

        .response-body {
            background: var(--bg-primary);
            padding: 1rem;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .json-viewer {
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* Key Information Panel */
        .key-info-panel {
            background: linear-gradient(135deg, var(--success-color), var(--info-color));
            background: rgb(16 185 129 / 0.05);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
        }

        .key-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .key-info-panel h4 {
            margin: 0;
            color: var(--success-color);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .key-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
        }

        .key-info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .key-info-item label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .key-value {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            padding: 0.25rem 0.5rem;
            background: var(--bg-primary);
            border-radius: 0.375rem;
            border: 1px solid var(--border-color);
            word-break: break-all;
        }

        .key-value.highlight {
            background: var(--success-color);
            color: white;
            border-color: var(--success-color);
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0.5rem;
            }

            .header-content {
                flex-direction: column;
                align-items: stretch;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .transactions-table {
                font-size: 0.75rem;
            }

            .transactions-table th,
            .transactions-table td {
                padding: 0.5rem;
            }
        }

        /* Color Variants */
        .text-success { color: var(--success-color) !important; }
        .text-warning { color: var(--warning-color) !important; }
        .text-error { color: var(--error-color) !important; }
        .text-info { color: var(--info-color); }

        .bg-success { background: var(--success-color); }
        .bg-warning { background: var(--warning-color); }
        .bg-error { background: var(--error-color); }
        .bg-info { background: var(--info-color); }

        /* Checkbox Styles */
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: background-color 0.2s ease;
        }

        .checkbox-label:hover {
            background: var(--bg-tertiary);
        }

        .checkbox-label input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .checkmark {
            position: relative;
            height: 20px;
            width: 20px;
            background-color: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .checkbox-label:hover input ~ .checkmark {
            border-color: var(--primary-color);
        }

        .checkbox-label input:checked ~ .checkmark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .checkbox-label input:checked ~ .checkmark:after {
            display: block;
        }

        /* Enhanced Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        /* Utility Classes */
        .hidden { display: none; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .font-bold { font-weight: 700; }
        .font-medium { font-weight: 500; }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .modal.hidden {
            display: none;
        }

        .modal-content {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            max-width: 90vw;
            max-height: 90vh;
            width: 100%;
            max-width: 1200px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: background-color 0.2s ease;
        }

        .modal-close:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        /* Log Details Styles */
        .log-details {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .detail-section {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .detail-section h4 {
            margin: 0 0 1rem 0;
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-item label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .detail-item span {
            color: var(--text-primary);
            padding: 0.5rem;
            background: var(--bg-primary);
            border-radius: 4px;
            border: 1px solid var(--border-color);
            font-size: 0.875rem;
        }

        .code-block {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .code-block pre {
            margin: 0;
            padding: 1rem;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            max-height: 400px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .error-block {
            border-color: var(--error-color);
            background: rgba(244, 67, 54, 0.1);
        }

        .error-block pre {
            color: var(--error-color);
        }
    </style>
</head>
<body data-theme="light">

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="header-title">
                    <i class="fas fa-flask"></i>
                    Pesapal Payment Testing Dashboard
                </h1>
                <div class="header-actions">
                    <button class="theme-toggle" onclick="toggleTheme()">
                        <i class="fas fa-moon"></i>
                    </button>
                    <span class="text-sm text-secondary">
                        Environment: <strong>{{ config('app.env') }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </header>

        <div class="container">
        
        <!-- Production Environment Notice -->
        <div class="alert-banner" style="background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 1rem; border-radius: 8px; margin: 1rem 0; text-align: center; box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);">
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-rocket" style="font-size: 1.2rem;"></i>
                <strong>🚀 PRODUCTION MODE ACTIVE</strong>
                <i class="fas fa-rocket" style="font-size: 1.2rem;"></i>
            </div>
            <div style="margin-top: 0.5rem; font-size: 0.9rem;">
                All payments will be processed through <strong>LIVE Pesapal Environment</strong> (https://pay.pesapal.com/v3/api)
            </div>
        </div>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Transactions</span>
                    <span class="stat-icon bg-info">
                        <i class="fas fa-chart-line text-white"></i>
                    </span>
                </div>
                <div class="stat-value text-info">{{ $stats['total_transactions'] ?? 0 }}</div>
                <div class="stat-change text-secondary">All time transactions</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Successful Payments</span>
                    <span class="stat-icon bg-success">
                        <i class="fas fa-check-circle text-white"></i>
                    </span>
                </div>
                <div class="stat-value text-success">{{ $stats['successful_payments'] ?? 0 }}</div>
                <div class="stat-change text-secondary">Completed transactions</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Pending Payments</span>
                    <span class="stat-icon bg-warning">
                        <i class="fas fa-clock text-white"></i>
                    </span>
                </div>
                <div class="stat-value text-warning">{{ $stats['pending_payments'] ?? 0 }}</div>
                <div class="stat-change text-secondary">Awaiting completion</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Failed Payments</span>
                    <span class="stat-icon bg-error">
                        <i class="fas fa-times-circle text-white"></i>
                    </span>
                </div>
                <div class="stat-value text-error">{{ $stats['failed_payments'] ?? 0 }}</div>
                <div class="stat-change text-secondary">Failed transactions</div>
            </div>
        </div>

        <!-- Action Sections -->
        <div class="action-grid">
            
            <!-- Payment Testing -->
            <div class="action-section">
                <h3 class="section-title">
                    <i class="fas fa-credit-card"></i>
                    Payment Testing
                </h3>
                
                <form id="payment-form" class="form-group">
                    <!-- Enhanced Basic Fields -->
                    <div class="form-group">
                        <label class="form-label">💰 Amount (UGX) - No Limits</label>
                        <input type="number" class="form-input" name="amount" value="50000" min="1" step="0.01">
                        <small class="text-secondary">Enter any amount for testing (no minimum/maximum limits)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">💱 Currency</label>
                        <select class="form-select" name="currency">
                            <option value="UGX">UGX - Ugandan Shilling</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="KES">KES - Kenyan Shilling</option>
                        </select>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="form-group">
                        <label class="form-label">👤 Customer Name</label>
                        <input type="text" class="form-input" name="customer_name" value="Test Customer" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📧 Customer Email</label>
                        <input type="email" class="form-input" name="customer_email" value="test@blitxpress.test" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📱 Customer Phone</label>
                        <input type="tel" class="form-input" name="customer_phone" value="+256700000000" required>
                        <small class="text-secondary">Format: +256XXXXXXXXX</small>
                    </div>
                    
                    <!-- Enhanced Request Parameters -->
                    <div class="form-group">
                        <label class="form-label">📝 Payment Description</label>
                        <input type="text" class="form-input" name="description" value="Test Payment - BlitXpress" required>
                        <small class="text-secondary">Detailed description helps with Pesapal processing</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">🔗 Merchant Reference</label>
                        <input type="text" class="form-input" name="merchant_reference" value="BX-TEST-{{ date('YmdHis') }}" required>
                        <small class="text-secondary">Unique reference for this transaction</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">🔍 Order Tracking ID</label>
                        <div class="input-group">
                            <input type="text" class="form-input" name="order_tracking_id" placeholder="Enter existing order tracking ID (optional)">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="clearTrackingId()">Clear</button>
                        </div>
                        <small class="text-info">💡 Use this to check status of existing payment or leave blank for new payment</small>
                        <small class="text-secondary">🎯 Tip: Click on tracking ID in response panel to auto-populate this field</small>
                    </div>
                    
                    <!-- API Configuration Fields -->
                    <div class="form-group">
                        <label class="form-label">🔄 Callback URL</label>
                        <input type="url" class="form-input" name="callback_url" value="{{ url('/payment-test/callback') }}" required>
                        <small class="text-success">✅ Auto-filled with test callback URL</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">🔔 Notification ID (IPN)</label>
                        <input type="text" class="form-input" name="notification_id" placeholder="Leave blank to auto-register IPN">
                        <small class="text-secondary">Optional: Use existing IPN or auto-create new one</small>
                    </div>
                    
                    <!-- Advanced Debugging Fields -->
                    <div class="form-group">
                        <label class="form-label">🌍 API Environment</label>
                        <select class="form-select" name="api_environment">
                            <option value="production" selected>🚀 Production (Live)</option>
                            <option value="sandbox">🧪 Sandbox (Testing)</option>
                        </select>
                        <small class="text-success">✅ Production environment selected for live payments</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📊 Request Timeout (seconds)</label>
                        <input type="number" class="form-input" name="request_timeout" value="30" min="10" max="120">
                        <small class="text-secondary">API request timeout (10-120 seconds)</small>
                    </div>
                    
                    <!-- Error Handling Configuration -->
                    <div class="form-group">
                        <label class="form-label">🐛 Debug Mode</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="debug_mode" value="1" checked>
                                <span class="checkmark"></span>
                                Enable detailed API logging
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="validate_response" value="1" checked>
                                <span class="checkmark"></span>
                                Validate API response structure
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="retry_on_failure" value="1">
                                <span class="checkmark"></span>
                                Auto-retry on API failures
                            </label>
                        </div>
                        <small class="text-info">🔍 Help diagnose order_tracking_id & redirect_url issues</small>
                    </div>
                    
                    <!-- Payment Method Selection -->
                    <div class="form-group">
                        <label class="form-label">💳 Payment Method (Optional)</label>
                        <select class="form-select" name="payment_method">
                            <option value="">All Available Methods</option>
                            <option value="CARD">Credit/Debit Card</option>
                            <option value="MOBILEMONEY">Mobile Money</option>
                            <option value="BANK">Bank Transfer</option>
                            <option value="VISA">Visa</option>
                            <option value="MASTERCARD">Mastercard</option>
                        </select>
                        <small class="text-secondary">Restrict payment methods for testing</small>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play"></i>
                            Initialize Payment
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="generateTestData()">
                            <i class="fas fa-random"></i>
                            Random Data
                        </button>
                    </div>
                </form>
            </div>

            <!-- Status Checking -->
            <div class="action-section">
                <h3 class="section-title">
                    <i class="fas fa-search"></i>
                    Status Checking
                </h3>
                
                <form id="status-form" class="form-group">
                    <div class="form-group">
                        <label class="form-label">Order ID</label>
                        <input type="number" class="form-input" name="order_id" placeholder="Enter order ID">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Or Tracking ID</label>
                        <input type="text" class="form-input" name="tracking_id" placeholder="Enter tracking ID">
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Check Status
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="refreshStatus()">
                            <i class="fas fa-sync"></i>
                            Refresh
                        </button>
                    </div>
                </form>
            </div>

            <!-- Test Scenarios -->
            <div class="action-section">
                <h3 class="section-title">
                    <i class="fas fa-flask"></i>
                    Test Scenarios
                </h3>
                
                <div class="form-group">
                    <label class="form-label">Scenario Type</label>
                    <select class="form-select" id="scenario-select">
                        <option value="success">✅ Success Scenario</option>
                        <option value="high_amount">💰 High Amount Test</option>
                        <option value="minimal">🪙 Minimal Amount</option>
                        <option value="special_chars">🔤 Special Characters</option>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" onclick="runScenario()">
                        <i class="fas fa-play"></i>
                        Run Scenario
                    </button>
                    <button type="button" class="btn btn-warning" onclick="bulkTest()">
                        <i class="fas fa-layer-group"></i>
                        Bulk Test
                    </button>
                </div>
            </div>

            <!-- Tools & Utilities -->
            <div class="action-section">
                <h3 class="section-title">
                    <i class="fas fa-tools"></i>
                    Tools & Utilities
                </h3>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-success" onclick="getAnalytics()">
                        <i class="fas fa-chart-bar"></i>
                        Analytics
                    </button>
                    <button type="button" class="btn btn-info" onclick="testConfig()">
                        <i class="fas fa-cog"></i>
                        Test Config
                    </button>
                    <button type="button" class="btn btn-warning" onclick="simulateCallback()">
                        <i class="fas fa-phone"></i>
                        Simulate Callback
                    </button>
                    <button type="button" class="btn btn-danger" onclick="cleanupData()">
                        <i class="fas fa-trash"></i>
                        Cleanup Test Data
                    </button>
                </div>
            </div>

        </div>

        <!-- Response Display -->
        <div id="response-container" class="response-container hidden">
            <div class="response-header">
                <span id="response-title">Response</span>
                <span id="response-status"></span>
            </div>
            
            <!-- Key Information Panel -->
            <div id="key-info-panel" class="key-info-panel hidden">
                <div class="key-info-header">
                    <h4>🔑 Key Information</h4>
                    <button id="check-status-btn" class="btn btn-sm btn-primary hidden" onclick="checkCurrentTransactionStatus()">
                        <i class="fas fa-sync"></i> Check Status
                    </button>
                </div>
                <div class="key-info-grid">
                    <div class="key-info-item">
                        <label>Order Tracking ID:</label>
                        <span id="key-tracking-id" class="key-value">-</span>
                    </div>
                    <div class="key-info-item">
                        <label>Merchant Reference:</label>
                        <span id="key-merchant-ref" class="key-value">-</span>
                    </div>
                    <div class="key-info-item">
                        <label>Amount:</label>
                        <span id="key-amount" class="key-value">-</span>
                    </div>
                    <div class="key-info-item">
                        <label>Redirect URL:</label>
                        <span id="key-redirect-url" class="key-value">-</span>
                    </div>
                    <div class="key-info-item">
                        <label>Response Time:</label>
                        <span id="key-response-time" class="key-value">-</span>
                    </div>
                </div>
            </div>
            
            <div class="response-body">
                <pre id="response-content" class="json-viewer"></pre>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="transactions-section">
            <div class="transactions-header">
                <h3 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Transactions
                </h3>
            </div>
            
            @if(count($recentTransactions) > 0)
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTransactions as $transaction)
                    <tr>
                        <td class="font-mono">#{{ $transaction->id }}</td>
                        <td>
                            @if($transaction->order)
                                <span class="font-mono">{{ $transaction->order->order_code }}</span>
                            @else
                                <span class="text-light">No order</span>
                            @endif
                        </td>
                        <td class="font-mono">UGX {{ number_format($transaction->amount) }}</td>
                        <td>
                            <span class="status-badge status-{{ strtolower($transaction->status) }}">
                                {{ $transaction->status }}
                            </span>
                        </td>
                        <td>{{ $transaction->payment_method ?: 'N/A' }}</td>
                        <td class="text-sm">{{ $transaction->created_at->diffForHumans() }}</td>
                        <td>
                            <button class="btn btn-secondary text-xs" onclick="checkTransactionStatus('{{ $transaction->order_tracking_id }}')">
                                <i class="fas fa-search"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <p>No transactions found. Start testing to see results here!</p>
            </div>
            @endif
        </div>

        <!-- Test Logs -->
        <div class="transactions-section">
            <div class="transactions-header">
                <h3 class="section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Recent Test Logs
                    <span class="text-sm text-secondary">({{ $logStats['total'] }} total, {{ $logStats['success_rate'] }}% success rate)</span>
                </h3>
            </div>
            
            @if(count($recentLogs) > 0)
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Customer</th>
                        <th>Response Time</th>
                        <th>Message</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    <tr>
                        <td class="font-mono">#{{ $log->id }}</td>
                        <td>
                            <span class="text-xs">{{ strtoupper($log->action) }}</span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $log->status_badge }}">
                                {{ $log->status_icon }} {{ $log->success ? 'SUCCESS' : 'FAILED' }}
                            </span>
                        </td>
                        <td class="font-mono text-sm">{{ $log->formatted_amount }}</td>
                        <td class="text-sm">{{ $log->customer_name ?: 'N/A' }}</td>
                        <td class="font-mono text-sm">{{ $log->response_time }}</td>
                        <td class="text-sm" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $log->message ?: $log->error_message }}
                        </td>
                        <td class="text-sm">{{ $log->created_at->diffForHumans() }}</td>
                        <td>
                            <button class="btn btn-secondary text-xs" onclick="viewLogDetails({{ $log->id }})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                <i class="fas fa-clipboard" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <p>No test logs found. Start testing to see detailed logs here!</p>
            </div>
            @endif
        </div>

    </div>

    <!-- Log Details Modal -->
    <div id="logModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="logModalTitle">Log Details</h3>
                <button class="modal-close" onclick="closeLogModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="log-details">
                    <div class="detail-section">
                        <h4><i class="fas fa-info-circle"></i> Basic Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Log ID:</label>
                                <span id="logId" class="font-mono"></span>
                            </div>
                            <div class="detail-item">
                                <label>Action:</label>
                                <span id="logAction"></span>
                            </div>
                            <div class="detail-item">
                                <label>Status:</label>
                                <span id="logStatus"></span>
                            </div>
                            <div class="detail-item">
                                <label>Response Time:</label>
                                <span id="logResponseTime" class="font-mono"></span>
                            </div>
                            <div class="detail-item">
                                <label>Created:</label>
                                <span id="logCreated"></span>
                            </div>
                            <div class="detail-item">
                                <label>Message:</label>
                                <span id="logMessage"></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4><i class="fas fa-upload"></i> Request Data (POST to Pesapal)</h4>
                        <div class="code-block">
                            <pre id="logRequestData" class="json-viewer"></pre>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4><i class="fas fa-download"></i> Response Data (From Pesapal)</h4>
                        <div class="code-block">
                            <pre id="logResponseData" class="json-viewer"></pre>
                        </div>
                    </div>

                    <div class="detail-section" id="errorSection" style="display: none;">
                        <h4><i class="fas fa-exclamation-triangle"></i> Error Details</h4>
                        <div class="code-block error-block">
                            <pre id="logErrorData" class="json-viewer"></pre>
                        </div>
                    </div>

                    <div class="detail-section" id="debugSection" style="display: none;">
                        <h4><i class="fas fa-bug"></i> Debug Information</h4>
                        <div class="code-block">
                            <pre id="logDebugData" class="json-viewer"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeLogModal()">
                    <i class="fas fa-times"></i> Close
                </button>
                <button class="btn btn-primary" onclick="downloadLogData()">
                    <i class="fas fa-download"></i> Download JSON
                </button>
            </div>
        </div>
    </div>

    <script>
        // CSRF token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Theme management
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            const icon = document.querySelector('.theme-toggle i');
            icon.className = newTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            const icon = document.querySelector('.theme-toggle i');
            icon.className = savedTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        });

        // Utility functions
        function showResponse(title, data, isSuccess = true) {
            const container = document.getElementById('response-container');
            const titleEl = document.getElementById('response-title');
            const statusEl = document.getElementById('response-status');
            const contentEl = document.getElementById('response-content');
            const keyInfoPanel = document.getElementById('key-info-panel');
            
            titleEl.textContent = title;
            statusEl.textContent = isSuccess ? '✅ Success' : '❌ Error';
            
            // Clear all previous classes and set the correct one
            statusEl.className = '';
            statusEl.classList.add(isSuccess ? 'text-success' : 'text-error');
            
            // Add additional styling for better visibility
            statusEl.style.fontWeight = 'bold';
            statusEl.style.fontSize = '0.95rem';
            
            // Debug logging
            console.log('🎨 Status styling applied:', {
                isSuccess: isSuccess,
                className: statusEl.className,
                textContent: statusEl.textContent,
                computedColor: window.getComputedStyle(statusEl).color
            });
            
            // Populate key information panel if it's a successful payment response
            if (isSuccess && data && data.data && data.data.order_tracking_id) {
                keyInfoPanel.classList.remove('hidden');
                
                // Store the tracking ID for status checking
                window.currentTrackingId = data.data.order_tracking_id;
                
                // Show the check status button
                document.getElementById('check-status-btn').classList.remove('hidden');
                
                // Populate key values
                document.getElementById('key-tracking-id').textContent = data.data.order_tracking_id || 'N/A';
                document.getElementById('key-merchant-ref').textContent = data.data.test_info?.test_id || data.data.test_data?.merchant_reference || 'N/A';
                document.getElementById('key-amount').textContent = `${data.data.test_data?.amount || 'N/A'} ${data.data.test_data?.currency || 'UGX'}`;
                document.getElementById('key-redirect-url').textContent = data.data.redirect_url || 'N/A';
                document.getElementById('key-response-time').textContent = data.data.test_info?.response_time || 'N/A';
                
                // Highlight the tracking ID
                document.getElementById('key-tracking-id').classList.add('highlight');
                
                // Make tracking ID clickable to populate form field
                document.getElementById('key-tracking-id').style.cursor = 'pointer';
                document.getElementById('key-tracking-id').title = 'Click to copy to form field';
                document.getElementById('key-tracking-id').onclick = function() {
                    document.querySelector('[name="order_tracking_id"]').value = data.data.order_tracking_id;
                    showResponse('📋 Tracking ID Copied', {
                        message: 'Order tracking ID has been copied to the form field',
                        tracking_id: data.data.order_tracking_id
                    }, true);
                };
                
                // Enhanced display with order_tracking_id highlighting
                let displayContent = '';
                displayContent += `🔍 ORDER TRACKING ID: ${data.data.order_tracking_id}\n`;
                displayContent += `📋 MERCHANT REFERENCE: ${data.data.test_info?.test_id || 'N/A'}\n`;
                displayContent += `💰 AMOUNT: ${data.data.test_data?.amount || 'N/A'} ${data.data.test_data?.currency || 'UGX'}\n`;
                displayContent += `🔗 REDIRECT URL: ${data.data.redirect_url || 'N/A'}\n`;
                displayContent += `⏱️  RESPONSE TIME: ${data.data.test_info?.response_time || 'N/A'}\n`;
                displayContent += '\n--- FULL RESPONSE ---\n';
                displayContent += JSON.stringify(data, null, 2);
                contentEl.textContent = displayContent;
            } else {
                keyInfoPanel.classList.add('hidden');
                contentEl.textContent = JSON.stringify(data, null, 2);
            }
            
            container.classList.remove('hidden');
            container.scrollIntoView({ behavior: 'smooth' });
        }

        function setLoading(element, isLoading) {
            if (isLoading) {
                element.classList.add('loading');
                const icon = element.querySelector('i');
                if (icon) {
                    icon.className = 'spinner';
                }
            } else {
                element.classList.remove('loading');
                // Restore original icon (you might want to store this)
            }
        }

        // API call helper
        async function apiCall(url, method = 'GET', data = null) {
            // Ensure we use the correct base URL for Laravel
            const baseUrl = '{{ url('/') }}';
            const fullUrl = url.startsWith('http') ? url : `${baseUrl}${url}`;
            
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            };
            
            if (data) {
                options.body = JSON.stringify(data);
            }
            
            const response = await fetch(fullUrl, options);
            return await response.json();
        }

        // Enhanced Payment initialization
        document.getElementById('payment-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            setLoading(submitBtn, true);
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            // Enhanced form validation
            const validationErrors = validatePaymentForm(data);
            if (validationErrors.length > 0) {
                showResponse('Validation Errors', { 
                    errors: validationErrors,
                    message: 'Please fix the following issues before submitting:'
                }, false);
                setLoading(submitBtn, false);
                return;
            }

            // Add checkboxes manually (FormData doesn't include unchecked checkboxes)
            data.debug_mode = e.target.querySelector('[name="debug_mode"]').checked ? '1' : '0';
            data.validate_response = e.target.querySelector('[name="validate_response"]').checked ? '1' : '0';
            data.retry_on_failure = e.target.querySelector('[name="retry_on_failure"]').checked ? '1' : '0';
            
            // Check if order_tracking_id is provided for status checking
            if (data.order_tracking_id && data.order_tracking_id.trim()) {
                // If order tracking ID is provided, check status instead of creating new payment
                console.log('🔍 Order tracking ID provided, checking status instead of creating new payment');
                
                try {
                    const statusResponse = await apiCall('/payment-test/status', 'POST', { 
                        tracking_id: data.order_tracking_id.trim() 
                    });
                    showResponse(`🔍 Status Check for ${data.order_tracking_id}`, statusResponse, statusResponse.success);
                    return;
                } catch (error) {
                    showResponse('Status Check Error', { error: error.message }, false);
                    return;
                } finally {
                    setLoading(submitBtn, false);
                }
            }
            
            // Enhanced logging for debugging
            console.log('🚀 Initiating payment with enhanced data:', {
                ...data,
                timestamp: new Date().toISOString()
            });
            
            try {
                const response = await apiCall('/payment-test/initialize', 'POST', data);
                
                // Enhanced response handling
                if (response.success) {
                    showResponse('✅ Payment Initialization Successful', response, true);
                    
                    // Check for redirect URL and offer to open
                    if (response.data.redirect_url) {
                        const openPayment = confirm('Payment initialized successfully! Would you like to open the Pesapal payment page?');
                        if (openPayment) {
                            window.open(response.data.redirect_url, '_blank');
                        }
                    } else if (response.data.payment_response && response.data.payment_response.redirect_url) {
                        const openPayment = confirm('Payment initialized! Would you like to open the Pesapal payment page?');
                        if (openPayment) {
                            window.open(response.data.payment_response.redirect_url, '_blank');
                        }
                    }
                } else {
                    // Enhanced error display
                    showResponse('💥 Payment Initialization Failed', response, false);
                    
                    // Show troubleshooting info if available
                    if (response.data && response.data.troubleshooting) {
                        console.group('🔧 Troubleshooting Information');
                        console.log('Issue Type:', response.data.troubleshooting.issue_type);
                        console.log('Description:', response.data.troubleshooting.description);
                        console.log('Next Steps:', response.data.troubleshooting.next_steps);
                        console.groupEnd();
                    }
                }
            } catch (error) {
                console.error('❌ Payment initialization error:', error);
                showResponse('💥 Payment Initialization Error', { 
                    error: error.message,
                    timestamp: new Date().toISOString(),
                    form_data: data
                }, false);
            } finally {
                setLoading(submitBtn, false);
            }
        });

        // Enhanced form validation function
        function validatePaymentForm(data) {
            const errors = [];
            
            // Amount validation
            if (!data.amount || parseFloat(data.amount) <= 0) {
                errors.push('Amount must be greater than 0');
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!data.customer_email || !emailRegex.test(data.customer_email)) {
                errors.push('Valid email address is required');
            }
            
            // Phone validation (basic)
            const phoneRegex = /^\+\d{10,15}$/;
            if (!data.customer_phone || !phoneRegex.test(data.customer_phone)) {
                errors.push('Phone number must start with + and contain 10-15 digits');
            }
            
            // Merchant reference validation
            if (data.merchant_reference && data.merchant_reference.length < 3) {
                errors.push('Merchant reference must be at least 3 characters long');
            }
            
            // Callback URL validation
            if (data.callback_url) {
                try {
                    new URL(data.callback_url);
                } catch {
                    errors.push('Callback URL must be a valid URL');
                }
            }
            
            // Timeout validation
            const timeout = parseInt(data.request_timeout);
            if (timeout < 10 || timeout > 120) {
                errors.push('Request timeout must be between 10 and 120 seconds');
            }
            
            return errors;
        }

        // Status checking
        document.getElementById('status-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            setLoading(submitBtn, true);
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await apiCall('/payment-test/status', 'POST', data);
                showResponse('Payment Status', response, response.success);
            } catch (error) {
                showResponse('Status Check Error', { error: error.message }, false);
            } finally {
                setLoading(submitBtn, false);
            }
        });

        // Enhanced test data generation
        async function generateTestData() {
            try {
                const response = await apiCall('/payment-test/generate-data');
                if (response.success) {
                    const form = document.getElementById('payment-form');
                    const data = response.data;
                    
                    // Basic fields
                    form.querySelector('[name="amount"]').value = data.amount;
                    form.querySelector('[name="customer_name"]').value = data.customer_name;
                    form.querySelector('[name="customer_email"]').value = data.customer_email;
                    form.querySelector('[name="customer_phone"]').value = data.customer_phone || '+256700000000';
                    form.querySelector('[name="description"]').value = data.description;
                    
                    // Enhanced fields
                    const merchantRef = 'BX-TEST-' + new Date().toISOString().replace(/[-:T]/g, '').substring(0, 14) + '-' + Math.random().toString(36).substring(2, 6).toUpperCase();
                    form.querySelector('[name="merchant_reference"]').value = merchantRef;
                    
                    // Clear order tracking ID for new payment generation
                    form.querySelector('[name="order_tracking_id"]').value = '';
                    
                    // Generate random currency occasionally
                    const currencies = ['UGX', 'USD', 'EUR', 'KES'];
                    if (Math.random() > 0.7) {
                        form.querySelector('[name="currency"]').value = currencies[Math.floor(Math.random() * currencies.length)];
                    }
                    
                    // Random timeout between 15-60 seconds
                    const timeout = Math.floor(Math.random() * (60 - 15 + 1)) + 15;
                    form.querySelector('[name="request_timeout"]').value = timeout;
                    
                    // Random payment method restriction (30% chance)
                    if (Math.random() > 0.7) {
                        const methods = ['', 'CARD', 'MOBILEMONEY', 'BANK', 'VISA', 'MASTERCARD'];
                        form.querySelector('[name="payment_method"]').value = methods[Math.floor(Math.random() * methods.length)];
                    }
                    
                    // Randomly toggle debug options
                    form.querySelector('[name="debug_mode"]').checked = Math.random() > 0.3; // 70% chance enabled
                    form.querySelector('[name="validate_response"]').checked = Math.random() > 0.2; // 80% chance enabled
                    form.querySelector('[name="retry_on_failure"]').checked = Math.random() > 0.6; // 40% chance enabled
                    
                    // Always default to production environment
                    form.querySelector('[name="api_environment"]').value = 'production';
                    
                    console.log('🎲 Generated enhanced test data:', {
                        merchant_reference: merchantRef,
                        timeout: timeout,
                        debug_enabled: form.querySelector('[name="debug_mode"]').checked
                    });
                    
                    showResponse('✅ Test Data Generated', {
                        message: 'Enhanced test data has been populated in all form fields',
                        generated_fields: [
                            'Basic customer information',
                            'Merchant reference',
                            'Random timeout',
                            'Debug settings',
                            'Payment method restriction'
                        ]
                    }, true);
                }
            } catch (error) {
                console.error('Failed to generate test data:', error);
                showResponse('❌ Test Data Generation Failed', { error: error.message }, false);
            }
        }

        // Run scenario test
        async function runScenario() {
            const scenario = document.getElementById('scenario-select').value;
            
            try {
                const response = await apiCall('/payment-test/scenarios', 'POST', { scenario });
                showResponse('Scenario Test', response, response.success);
                
                if (response.success && response.data.payment_response.redirect_url) {
                    const openPayment = confirm('Scenario test ready! Would you like to open the payment page?');
                    if (openPayment) {
                        window.open(response.data.payment_response.redirect_url, '_blank');
                    }
                }
            } catch (error) {
                showResponse('Scenario Test Error', { error: error.message }, false);
            }
        }

        // Bulk test
        async function bulkTest() {
            const count = prompt('How many test orders to create? (max 20)', '5');
            if (!count || isNaN(count)) return;
            
            try {
                const response = await apiCall('/payment-test/bulk-test', 'POST', { count: parseInt(count) });
                showResponse('Bulk Test', response, response.success);
            } catch (error) {
                showResponse('Bulk Test Error', { error: error.message }, false);
            }
        }

        // Get analytics
        async function getAnalytics() {
            try {
                const response = await apiCall('/payment-test/analytics');
                showResponse('Payment Analytics', response, response.success);
            } catch (error) {
                showResponse('Analytics Error', { error: error.message }, false);
            }
        }

        // Test configuration
        async function testConfig() {
            try {
                const response = await apiCall('/payment-test/config');
                showResponse('Configuration Test', response, response.success);
            } catch (error) {
                showResponse('Config Test Error', { error: error.message }, false);
            }
        }

        // Simulate callback
        async function simulateCallback() {
            const trackingId = prompt('Enter tracking ID to simulate callback for:');
            if (!trackingId) return;
            
            const status = prompt('Enter status (COMPLETED, FAILED, CANCELLED):', 'COMPLETED');
            if (!status) return;
            
            try {
                const response = await apiCall('/payment-test/simulate-callback', 'POST', { 
                    tracking_id: trackingId, 
                    status: status 
                });
                showResponse('Callback Simulation', response, response.success);
            } catch (error) {
                showResponse('Callback Simulation Error', { error: error.message }, false);
            }
        }

        // Cleanup test data
        async function cleanupData() {
            if (!confirm('Are you sure you want to delete all test data? This cannot be undone.')) return;
            
            try {
                const response = await apiCall('/payment-test/cleanup', 'DELETE');
                showResponse('Data Cleanup', response, response.success);
                
                if (response.success) {
                    // Refresh the page after successful cleanup
                    setTimeout(() => window.location.reload(), 2000);
                }
            } catch (error) {
                showResponse('Cleanup Error', { error: error.message }, false);
            }
        }

        // Check transaction status from table
        async function checkTransactionStatus(trackingId) {
            try {
                const response = await apiCall('/payment-test/status', 'POST', { tracking_id: trackingId });
                showResponse('Transaction Status', response, response.success);
            } catch (error) {
                showResponse('Status Check Error', { error: error.message }, false);
            }
        }

        // Check current transaction status (from key info panel)
        async function checkCurrentTransactionStatus() {
            if (!window.currentTrackingId) {
                showResponse('❌ No Tracking ID', { error: 'No order tracking ID available' }, false);
                return;
            }

            const button = document.getElementById('check-status-btn');
            setLoading(button, true);

            try {
                const response = await apiCall('/payment-test/status', 'POST', { tracking_id: window.currentTrackingId });
                showResponse(`🔍 Status Check for ${window.currentTrackingId}`, response, response.success);
            } catch (error) {
                showResponse('Status Check Error', { error: error.message }, false);
            } finally {
                setLoading(button, false);
            }
        }

        // Clear order tracking ID field
        function clearTrackingId() {
            document.querySelector('[name="order_tracking_id"]').value = '';
            showResponse('🗑️ Field Cleared', {
                message: 'Order tracking ID field has been cleared',
                action: 'Ready for new payment'
            }, true);
        }

        // Refresh status (for current form data)
        function refreshStatus() {
            document.getElementById('status-form').dispatchEvent(new Event('submit'));
        }

        // View log details in modal
        async function viewLogDetails(logId) {
            try {
                const response = await apiCall(`/payment-test/log/${logId}`);
                if (response.success) {
                    showLogModal(response.data);
                } else {
                    showResponse('❌ Failed to load log details', response, false);
                }
            } catch (error) {
                showResponse('Log Details Error', { error: error.message }, false);
            }
        }

        // Show log details modal
        function showLogModal(logData) {
            // Basic Information
            document.getElementById('logId').textContent = logData.id;
            document.getElementById('logAction').textContent = logData.action || 'N/A';
            document.getElementById('logStatus').innerHTML = logData.success ? 
                '<span class="status-badge status-success">✅ SUCCESS</span>' : 
                '<span class="status-badge status-failed">❌ FAILED</span>';
            document.getElementById('logResponseTime').textContent = logData.response_time_ms ? 
                logData.response_time_ms + 'ms' : 'N/A';
            document.getElementById('logCreated').textContent = logData.created_at || 'N/A';
            document.getElementById('logMessage').textContent = logData.message || logData.error_message || 'N/A';
            
            // Request Data (POST to Pesapal)
            const requestData = logData.request_data || logData.pesapal_request_payload || {};
            document.getElementById('logRequestData').textContent = JSON.stringify(requestData, null, 2);
            
            // Response Data (From Pesapal)
            const responseData = logData.response_data || logData.pesapal_response || {};
            document.getElementById('logResponseData').textContent = JSON.stringify(responseData, null, 2);
            
            // Error Details (if any)
            const errorSection = document.getElementById('errorSection');
            if (logData.error_details || logData.error_message) {
                const errorData = logData.error_details || { message: logData.error_message };
                document.getElementById('logErrorData').textContent = JSON.stringify(errorData, null, 2);
                errorSection.style.display = 'block';
            } else {
                errorSection.style.display = 'none';
            }
            
            // Debug Information (if any)
            const debugSection = document.getElementById('debugSection');
            if (logData.debug_info || logData.troubleshooting) {
                const debugData = {
                    debug_info: logData.debug_info,
                    troubleshooting: logData.troubleshooting,
                    environment: logData.api_environment,
                    tracking_id: logData.tracking_id,
                    merchant_reference: logData.merchant_reference
                };
                document.getElementById('logDebugData').textContent = JSON.stringify(debugData, null, 2);
                debugSection.style.display = 'block';
            } else {
                debugSection.style.display = 'none';
            }
            
            // Update modal title
            document.getElementById('logModalTitle').textContent = `Log Details #${logData.id} - ${logData.action || 'Payment Test'}`;
            
            // Store current log data for download
            window.currentLogData = logData;
            
            // Show modal
            document.getElementById('logModal').classList.remove('hidden');
        }

        // Close log modal
        function closeLogModal() {
            document.getElementById('logModal').classList.add('hidden');
        }

        // Download log data as JSON
        function downloadLogData() {
            if (window.currentLogData) {
                const dataStr = JSON.stringify(window.currentLogData, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const url = URL.createObjectURL(dataBlob);
                
                const link = document.createElement('a');
                link.href = url;
                link.download = `pesapal-log-${window.currentLogData.id}-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }
        }

        // Close modal when clicking outside
        document.getElementById('logModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogModal();
            }
        });

        // Auto-refresh data without full page refresh (every 30 seconds)
        setInterval(function() {
            // Only refresh specific sections if no modal/form is active
            if (!document.querySelector('.loading')) {
                // You can implement AJAX refresh of specific sections here
                console.log('Auto-refresh available (not implemented to avoid full page refresh)');
            }
        }, 30000);

    </script>

</body>
</html>
