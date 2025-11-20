<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Credentials - {{ env('APP_NAME') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 24px 30px;
            border-bottom: 3px solid #34495e;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 600;
        }

        .content {
            padding: 30px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .user-info {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .user-info h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-row {
            display: flex;
            padding: 8px 0;
            font-size: 14px;
        }

        .info-label {
            font-weight: 500;
            color: #6c757d;
            width: 100px;
            flex-shrink: 0;
        }

        .info-value {
            color: #212529;
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #dee2e6;
        }

        .btn {
            flex: 1;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        @media (max-width: 480px) {
            .buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Send User Credentials</h1>
        </div>

        <div class="content">
            @if(isset($success) && $success)
                <div class="alert alert-success">
                    <strong>Success!</strong> {{ $message }}
                </div>
            @endif

            @if(isset($error) && $error)
                <div class="alert alert-error">
                    <strong>Error:</strong> {{ $error }}
                </div>
            @endif

            @if(isset($user) && $user)
                <div class="user-info">
                    <h3>User Information</h3>
                    <div class="info-row">
                        <div class="info-label">Name:</div>
                        <div class="info-value">{{ $user->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value">{{ $user->email ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone:</div>
                        <div class="info-value">{{ $user->phone_number ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">User ID:</div>
                        <div class="info-value">#{{ $user->id }}</div>
                    </div>
                </div>
            @endif

            <div class="buttons">
                <a href="{{ admin_url('auth/users') }}" class="btn btn-secondary">
                    Back to Users
                </a>
                <button onclick="window.location.reload()" class="btn btn-primary">
                    Try Again
                </button>
            </div>
        </div>
    </div>
</body>
</html>
