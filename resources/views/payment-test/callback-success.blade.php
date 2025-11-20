<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎉 Payment Callback - Success</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --success-color: #10b981;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --border-radius: 0.75rem;
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--success-color), var(--primary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .callback-container {
            background: var(--bg-primary);
            padding: 3rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .callback-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .callback-message {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .callback-details {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-secondary);
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            margin: 0 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .url-params {
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: var(--border-radius);
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 1rem;
            text-align: left;
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .callback-container {
                padding: 2rem 1.5rem;
            }

            .success-icon {
                font-size: 3rem;
            }

            .callback-title {
                font-size: 1.5rem;
            }

            .detail-row {
                flex-direction: column;
                gap: 0.25rem;
            }

            .btn {
                width: 100%;
                margin: 0.25rem 0;
            }
        }
    </style>
</head>
<body>

    <div class="callback-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1 class="callback-title">Payment Callback Received!</h1>
        
        <p class="callback-message">
            This is the callback page where users would be redirected after completing their payment on Pesapal. 
            In a real implementation, this page would show the actual payment result and order details.
        </p>

        <div class="callback-details">
            <div class="detail-row">
                <span class="detail-label">Callback Type:</span>
                <span class="detail-value">Test Callback</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Timestamp:</span>
                <span class="detail-value" id="timestamp"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Environment:</span>
                <span class="detail-value">{{ config('app.env') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value text-success">✅ Callback Working</span>
            </div>
        </div>

        <div style="margin: 2rem 0;">
            <a href="{{ url('/payment-test') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <button class="btn btn-secondary" onclick="copyUrl()">
                <i class="fas fa-copy"></i>
                Copy URL
            </button>
        </div>

        <!-- URL Parameters Display -->
        <div class="url-params">
            <strong>URL Parameters Received:</strong><br>
            <span id="url-params">Loading...</span>
        </div>
    </div>

    <script>
        // Display current timestamp
        document.getElementById('timestamp').textContent = new Date().toISOString();

        // Display URL parameters
        function displayUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            const params = {};
            
            for (const [key, value] of urlParams) {
                params[key] = value;
            }
            
            const paramsDisplay = Object.keys(params).length > 0 
                ? JSON.stringify(params, null, 2)
                : 'No parameters received';
                
            document.getElementById('url-params').textContent = paramsDisplay;
        }

        // Copy current URL to clipboard
        function copyUrl() {
            navigator.clipboard.writeText(window.location.href).then(function() {
                const btn = event.target.closest('.btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = 'var(--success-color)';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '';
                }, 2000);
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            displayUrlParams();
        });

        // Auto-close in 30 seconds with countdown (optional)
        let countdown = 30;
        function updateCountdown() {
            if (countdown > 0) {
                // You can add a countdown display here if needed
                countdown--;
                setTimeout(updateCountdown, 1000);
            }
        }
        // updateCountdown(); // Uncomment to enable auto-close countdown
    </script>

</body>
</html>
