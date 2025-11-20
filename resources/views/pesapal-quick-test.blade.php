<!DOCTYPE html>
<html>
<head>
    <title>Pesapal Integration Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-form { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .result { background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #ffebee; color: #c62828; }
        input, select { padding: 8px; margin: 5px; width: 200px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Pesapal Integration Test</h1>
    
    <div class="test-form">
        <h3>Quick Payment Test</h3>
        <form id="testForm">
            <div>
                <label>Amount:</label>
                <input type="number" name="amount" value="1000" required>
            </div>
            <div>
                <label>Customer Name:</label>
                <input type="text" name="customer_name" value="Test Customer" required>
            </div>
            <div>
                <label>Customer Email:</label>
                <input type="email" name="customer_email" value="test@example.com" required>
            </div>
            <div>
                <label>Customer Phone:</label>
                <input type="tel" name="customer_phone" value="+256700000000" required>
            </div>
            <div>
                <label>Description:</label>
                <input type="text" name="description" value="Quick Test Payment" required>
            </div>
            <div>
                <button type="submit">Test Payment</button>
            </div>
        </form>
    </div>

    <div id="result" class="result" style="display: none;">
        <h3>Result:</h3>
        <pre id="resultContent"></pre>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            // Add required fields
            data.currency = 'UGX';
            data.merchant_reference = 'QUICK-TEST-' + Date.now();
            data.callback_url = window.location.origin + '/blitxpress/payment-test/callback';
            data.debug_mode = '1';
            data.validate_response = '1';
            data.api_environment = 'production';
            
            try {
                const response = await fetch('/blitxpress/payment-test/initialize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                document.getElementById('result').style.display = 'block';
                document.getElementById('result').className = result.success ? 'result' : 'result error';
                document.getElementById('resultContent').textContent = JSON.stringify(result, null, 2);
                
                if (result.success && result.data.redirect_url) {
                    if (confirm('Payment initialized! Open Pesapal payment page?')) {
                        window.open(result.data.redirect_url, '_blank');
                    }
                }
                
            } catch (error) {
                document.getElementById('result').style.display = 'block';
                document.getElementById('result').className = 'result error';
                document.getElementById('resultContent').textContent = 'Error: ' + error.message;
            }
        });
    </script>
</body>
</html>
