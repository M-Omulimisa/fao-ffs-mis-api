<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OneSignal Push Notification Testing Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notification-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .result-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
        }
        .error-box {
            background: #ffe7e7;
            border: 1px solid #ffb3b3;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">🔔 OneSignal Push Notification Testing Dashboard</h1>
                
                <!-- Debug Information -->
                <div class="alert alert-info mb-4">
                    <strong>🔧 Debug Information:</strong><br>
                    <small>
                        App URL: {{ env('APP_URL') }}<br>
                        API Base: <span id="debugBaseUrl"></span><br>
                        OneSignal App ID: {{ config('services.onesignal.app_id') }}<br>
                        Time: <span id="debugTime"></span>
                    </small>
                </div>
                
                <!-- Connection Test -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>📡 Connection Test</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <button onclick="pingAPI()" class="btn btn-info me-2">🏓 Ping API</button>
                            <button onclick="testConnection()" class="btn btn-primary">📡 Test OneSignal Connection</button>
                        </div>
                        <div id="connectionResult"></div>
                    </div>
                </div>

                <!-- Quick Send -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>🚀 Quick Send Notification</h5>
                    </div>
                    <div class="card-body">
                        <form id="quickSendForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" id="quickTitle" placeholder="Notification title" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-control" id="quickType">
                                            <option value="general">General</option>
                                            <option value="promotion">Promotion</option>
                                            <option value="order">Order Update</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" id="quickMessage" rows="3" placeholder="Notification message" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Action URL (Optional)</label>
                                        <input type="url" class="form-control" id="quickUrl" placeholder="https://example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Send To</label>
                                        <select class="form-control" id="quickTarget">
                                            <option value="all">All Users</option>
                                            <option value="segments">Specific Segments</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div id="segmentInput" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Segments (comma separated)</label>
                                    <input type="text" class="form-control" id="quickSegments" placeholder="All, Active Users, etc.">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">📤 Send Notification</button>
                        </form>
                        <div id="quickSendResult"></div>
                    </div>
                </div>

                <!-- Advanced Test -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>⚙️ Advanced Notification Test</h5>
                    </div>
                    <div class="card-body">
                        <form id="advancedForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" id="advTitle" placeholder="Advanced notification title" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Subtitle</label>
                                        <input type="text" class="form-control" id="advSubtitle" placeholder="Optional subtitle">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" id="advMessage" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Large Icon URL</label>
                                        <input type="url" class="form-control" id="advLargeIcon" placeholder="https://example.com/icon.png">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Big Picture URL</label>
                                        <input type="url" class="form-control" id="advBigPicture" placeholder="https://example.com/image.jpg">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Custom Data (JSON)</label>
                                <textarea class="form-control" id="advData" rows="2" placeholder='{"key": "value", "category": "promotion"}'></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning">🎯 Send Advanced Notification</button>
                        </form>
                        <div id="advancedResult"></div>
                    </div>
                </div>

                <!-- Recent Notifications -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>📋 Recent Notifications</h5>
                        <button onclick="loadNotifications()" class="btn btn-sm btn-outline-primary">🔄 Refresh</button>
                    </div>
                    <div class="card-body">
                        <div id="notificationsList">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Get base URL from Laravel environment
        const APP_URL = '{{ rtrim(env("APP_URL"), "/") }}';
        const BASE_URL = APP_URL + '/api/onesignal';
        
        console.log('OneSignal Test Dashboard Loaded');
        console.log('App URL:', APP_URL);
        console.log('API Base URL:', BASE_URL);

        // Show/hide segments input
        document.getElementById('quickTarget').addEventListener('change', function() {
            const segmentInput = document.getElementById('segmentInput');
            if (this.value === 'segments') {
                segmentInput.style.display = 'block';
            } else {
                segmentInput.style.display = 'none';
            }
        });

        // Simple API ping test
        async function pingAPI() {
            const resultDiv = document.getElementById('connectionResult');
            resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Pinging API...';

            try {
                console.log('Pinging API at:', BASE_URL + '/ping');
                
                const response = await fetch(BASE_URL + '/ping', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                console.log('Ping response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('Ping result:', result);
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="result-box">
                            <strong>✅ API Connection Successful!</strong><br>
                            <small>${result.message} at ${result.timestamp}</small>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error-box">
                            <strong>❌ API Ping Failed!</strong><br>
                            <small>${result.message || 'Unknown error'}</small>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('API ping error:', error);
                resultDiv.innerHTML = `
                    <div class="error-box">
                        <strong>❌ API Connection Error!</strong><br>
                        <small>URL: ${BASE_URL}/ping<br>Error: ${error.message}</small>
                    </div>
                `;
            }
        }

        // Test OneSignal connection
        async function testConnection() {
            const resultDiv = document.getElementById('connectionResult');
            resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Testing connection...';

            try {
                console.log('Testing connection to:', BASE_URL + '/test-connection');
                
                const response = await fetch(BASE_URL + '/test-connection', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('Test connection result:', result);
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="result-box">
                            <strong>✅ Connection Successful!</strong><br>
                            <small>Total Users: ${result.total_users || 0} | Messageable: ${result.messageable_users || 0}</small>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error-box">
                            <strong>❌ Connection Failed!</strong><br>
                            <small>${result.error || 'Unknown error'}</small>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Connection test error:', error);
                resultDiv.innerHTML = `
                    <div class="error-box">
                        <strong>❌ Network Error!</strong><br>
                        <small>URL: ${BASE_URL}/test-connection<br>Error: ${error.message}</small>
                    </div>
                `;
            }
        }

        // Quick send form
        document.getElementById('quickSendForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const resultDiv = document.getElementById('quickSendResult');
            resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Sending notification...';

            const formData = {
                title: document.getElementById('quickTitle').value,
                message: document.getElementById('quickMessage').value,
                type: document.getElementById('quickType').value,
                url: document.getElementById('quickUrl').value || null,
                target: document.getElementById('quickTarget').value,
                segments: document.getElementById('quickTarget').value === 'segments' 
                    ? document.getElementById('quickSegments').value.split(',').map(s => s.trim()).filter(s => s)
                    : null
            };

            try {
                console.log('Sending notification to:', BASE_URL + '/send');
                console.log('Form data:', formData);

                const response = await fetch(BASE_URL + '/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                console.log('Send response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('Send notification result:', result);
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="result-box">
                            <strong>✅ Notification Sent!</strong><br>
                            <small>Recipients: ${result.recipients || 0} | ID: ${result.notification_id || 'N/A'}</small>
                        </div>
                    `;
                    document.getElementById('quickSendForm').reset();
                    loadNotifications(); // Refresh the list
                } else {
                    resultDiv.innerHTML = `
                        <div class="error-box">
                            <strong>❌ Send Failed!</strong><br>
                            <small>${result.error || 'Unknown error'}</small>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Send notification error:', error);
                resultDiv.innerHTML = `
                    <div class="error-box">
                        <strong>❌ Network Error!</strong><br>
                        <small>URL: ${BASE_URL}/send<br>Error: ${error.message}</small>
                    </div>
                `;
            }
        });

        // Advanced form
        document.getElementById('advancedForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const resultDiv = document.getElementById('advancedResult');
            resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Sending advanced notification...';

            let customData = null;
            const dataInput = document.getElementById('advData').value.trim();
            if (dataInput) {
                try {
                    customData = JSON.parse(dataInput);
                } catch (error) {
                    resultDiv.innerHTML = `
                        <div class="error-box">
                            <strong>❌ Invalid JSON in Custom Data!</strong><br>
                            <small>${error.message}</small>
                        </div>
                    `;
                    return;
                }
            }

            const formData = {
                title: document.getElementById('advTitle').value,
                message: document.getElementById('advMessage').value,
                subtitle: document.getElementById('advSubtitle').value || null,
                large_icon: document.getElementById('advLargeIcon').value || null,
                big_picture: document.getElementById('advBigPicture').value || null,
                data: customData,
                advanced: true
            };

            try {
                const response = await fetch(BASE_URL + '/send-advanced', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="result-box">
                            <strong>✅ Advanced Notification Sent!</strong><br>
                            <small>Recipients: ${result.recipients || 0} | ID: ${result.notification_id || 'N/A'}</small>
                        </div>
                    `;
                    document.getElementById('advancedForm').reset();
                    loadNotifications(); // Refresh the list
                } else {
                    resultDiv.innerHTML = `
                        <div class="error-box">
                            <strong>❌ Send Failed!</strong><br>
                            <small>${result.error || 'Unknown error'}</small>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error-box">
                        <strong>❌ Error!</strong><br>
                        <small>${error.message}</small>
                    </div>
                `;
            }
        });

        // Load recent notifications
        async function loadNotifications() {
            const listDiv = document.getElementById('notificationsList');
            listDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

            try {
                console.log('Loading notifications from:', BASE_URL + '/recent');
                
                const response = await fetch(BASE_URL + '/recent', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                console.log('Load notifications response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                console.log('Load notifications result:', result);
                
                if (result.success && result.notifications && result.notifications.length > 0) {
                    listDiv.innerHTML = result.notifications.map(notification => `
                        <div class="notification-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${escapeHtml(notification.title || 'No title')}</h6>
                                    <p class="mb-2 text-muted">${escapeHtml(notification.message || 'No message')}</p>
                                    <small class="text-muted">
                                        ${escapeHtml(notification.target_description || 'Unknown target')} • 
                                        Recipients: ${notification.recipients || 0} • 
                                        ${formatDate(notification.created_at)}
                                    </small>
                                </div>
                                <span class="badge status-badge ${getStatusColor(notification.status)}">${(notification.status || 'unknown').toUpperCase()}</span>
                            </div>
                            ${notification.error_message ? `<div class="mt-2 text-danger small">Error: ${escapeHtml(notification.error_message)}</div>` : ''}
                        </div>
                    `).join('');
                } else {
                    listDiv.innerHTML = '<div class="text-center text-muted">No notifications found</div>';
                }
            } catch (error) {
                console.error('Load notifications error:', error);
                listDiv.innerHTML = `
                    <div class="error-box">
                        <strong>Error loading notifications:</strong><br>
                        <small>URL: ${BASE_URL}/recent<br>Error: ${error.message}</small>
                    </div>
                `;
            }
        }

        // Helper functions
        function getCSRFToken() {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!token) {
                console.warn('CSRF token not found! Make sure meta tag is present.');
                return '';
            }
            return token;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? String(text).replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
        }

        function getStatusColor(status) {
            const colors = {
                'pending': 'bg-warning',
                'sent': 'bg-success',
                'failed': 'bg-danger',
                'cancelled': 'bg-secondary'
            };
            return colors[status] || 'bg-secondary';
        }

        function formatDate(dateString) {
            if (!dateString) return 'Unknown date';
            try {
                return new Date(dateString).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return 'Invalid date';
            }
        }

        // Load notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, initializing...');
            console.log('CSRF Token:', getCSRFToken() ? 'Present' : 'Missing');
            
            // Update debug information
            document.getElementById('debugBaseUrl').textContent = BASE_URL;
            document.getElementById('debugTime').textContent = new Date().toLocaleString();
            
            loadNotifications();
        });
    </script>
</body>
</html>
