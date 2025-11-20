<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    protected $client;
    protected $appId;
    protected $restApiKey;
    protected $androidChannelId;
    protected $baseUrl = 'https://onesignal.com/api/v1/';

    public function __construct()
    {
        $this->client = new Client();
        $this->appId = config('services.onesignal.app_id');
        $this->restApiKey = config('services.onesignal.rest_api_key');
        $this->androidChannelId = config('services.onesignal.android_channel_id');
    }

        /**
     * Send notification to all registered users with rich media support
     *
     * @param string $title
     * @param string $message
     * @param array $data
     * @param string|null $url
     * @param string|null $largeIcon
     * @param string|null $bigPicture
     * @return array
     */
    public function sendToAll($title, $message, $data = [], $url = null, $largeIcon = null, $bigPicture = null)
    {
        $payload = [
            'app_id' => $this->appId,
            'included_segments' => ['All'],
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        // Add Android channel ID if available
        if ($this->androidChannelId) {
            $payload['android_channel_id'] = $this->androidChannelId;
        }

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        if ($url) {
            $payload['url'] = $url;
        }

        // Add rich media support
        if ($largeIcon) {
            $payload['large_icon'] = $largeIcon;
        }

        if ($bigPicture) {
            $payload['big_picture'] = $bigPicture;
            // For Android, also set the chrome_web_image for web notifications
            $payload['chrome_web_image'] = $bigPicture;
        }

        return $this->sendNotification($payload);
    }

    /**
     * Send notification to specific users by user IDs with rich media support
     *
     * @param array $userIds
     * @param string $title
     * @param string $message
     * @param array $data
     * @param string|null $url
     * @param string|null $largeIcon
     * @param string|null $bigPicture
     * @return array
     */
    public function sendToUsers($userIds, $title, $message, $data = [], $url = null, $largeIcon = null, $bigPicture = null)
    {
        $payload = [
            'app_id' => $this->appId,
            'include_external_user_ids' => $userIds,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        // Add Android channel ID if available
        if ($this->androidChannelId) {
            $payload['android_channel_id'] = $this->androidChannelId;
        }

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        if ($url) {
            $payload['url'] = $url;
        }

        // Add rich media support
        if ($largeIcon) {
            $payload['large_icon'] = $largeIcon;
        }

        if ($bigPicture) {
            $payload['big_picture'] = $bigPicture;
            // For Android, also set the chrome_web_image for web notifications
            $payload['chrome_web_image'] = $bigPicture;
        }

        return $this->sendNotification($payload);
    }

    /**
     * Send notification to specific player IDs (OneSignal device IDs) with rich media support
     *
     * @param array $playerIds
     * @param string $title
     * @param string $message
     * @param array $data
     * @param string|null $url
     * @param string|null $largeIcon
     * @param string|null $bigPicture
     * @return array
     */
    public function sendToPlayers($playerIds, $title, $message, $data = [], $url = null, $largeIcon = null, $bigPicture = null)
    {
        $payload = [
            'app_id' => $this->appId,
            'include_player_ids' => $playerIds,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        // Add Android channel ID if available
        if ($this->androidChannelId) {
            $payload['android_channel_id'] = $this->androidChannelId;
        }

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        if ($url) {
            $payload['url'] = $url;
        }

        // Add rich media support
        if ($largeIcon) {
            $payload['large_icon'] = $largeIcon;
        }

        if ($bigPicture) {
            $payload['big_picture'] = $bigPicture;
            // For Android, also set the chrome_web_image for web notifications
            $payload['chrome_web_image'] = $bigPicture;
        }

        return $this->sendNotification($payload);
    }

    /**
     * Send notification with custom segments and rich media support
     *
     * @param array $segments
     * @param string $title
     * @param string $message
     * @param array $data
     * @param string|null $url
     * @param string|null $largeIcon
     * @param string|null $bigPicture
     * @return array
     */
    public function sendToSegments($segments, $title, $message, $data = [], $url = null, $largeIcon = null, $bigPicture = null)
    {
        $payload = [
            'app_id' => $this->appId,
            'included_segments' => $segments,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        // Add Android channel ID if available
        if ($this->androidChannelId) {
            $payload['android_channel_id'] = $this->androidChannelId;
        }

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        if ($url) {
            $payload['url'] = $url;
        }

        // Add rich media support
        if ($largeIcon) {
            $payload['large_icon'] = $largeIcon;
        }

        if ($bigPicture) {
            $payload['big_picture'] = $bigPicture;
            // For Android, also set the chrome_web_image for web notifications
            $payload['chrome_web_image'] = $bigPicture;
        }

        return $this->sendNotification($payload);
    }

    /**
     * Send advanced notification with filters
     *
     * @param array $filters
     * @param string $title
     * @param string $message
     * @param array $data
     * @param string|null $url
     * @param string|null $subtitle
     * @param string|null $largeIcon
     * @param string|null $bigPicture
     * @return array
     */
    public function sendAdvanced($filters, $title, $message, $data = [], $url = null, $subtitle = null, $largeIcon = null, $bigPicture = null)
    {
        $payload = [
            'app_id' => $this->appId,
            'filters' => $filters,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        // Add Android channel ID if available
        if ($this->androidChannelId) {
            $payload['android_channel_id'] = $this->androidChannelId;
        }

        if ($subtitle) {
            $payload['subtitle'] = ['en' => $subtitle];
        }

        if ($largeIcon) {
            $payload['large_icon'] = $largeIcon;
        }

        if ($bigPicture) {
            $payload['big_picture'] = $bigPicture;
        }

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        if ($url) {
            $payload['url'] = $url;
        }

        return $this->sendNotification($payload);
    }

    /**
     * Core method to send notification via OneSignal API
     *
     * @param array $payload
     * @return array
     */
    protected function sendNotification($payload)
    {
        try {
            Log::info('OneSignal: Sending notification', $payload);

            $response = $this->client->post($this->baseUrl . 'notifications', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . $this->restApiKey,
                ],
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody(), true);
            
            Log::info('OneSignal: Notification sent successfully', $result);

            return [
                'success' => true,
                'data' => $result,
                'recipients' => $result['recipients'] ?? 0,
                'notification_id' => $result['id'] ?? null,
            ];

        } catch (GuzzleException $e) {
            Log::error('OneSignal: Failed to send notification', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'recipients' => 0,
                'notification_id' => null,
            ];
        }
    }

    /**
     * Get notification details by ID
     *
     * @param string $notificationId
     * @return array
     */
    public function getNotification($notificationId)
    {
        try {
            $response = $this->client->get($this->baseUrl . "notifications/{$notificationId}?app_id={$this->appId}", [
                'headers' => [
                    'Authorization' => 'Basic ' . $this->restApiKey,
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $result,
            ];

        } catch (GuzzleException $e) {
            Log::error('OneSignal: Failed to get notification', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get app statistics
     *
     * @return array
     */
    public function getAppStats()
    {
        try {
            $response = $this->client->get($this->baseUrl . "apps/{$this->appId}", [
                'headers' => [
                    'Authorization' => 'Basic ' . $this->restApiKey,
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $result,
                'total_users' => $result['players'] ?? 0,
                'messageable_users' => $result['messageable_players'] ?? 0,
            ];

        } catch (GuzzleException $e) {
            Log::error('OneSignal: Failed to get app stats', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel notification
     *
     * @param string $notificationId
     * @return array
     */
    public function cancelNotification($notificationId)
    {
        try {
            $response = $this->client->delete($this->baseUrl . "notifications/{$notificationId}?app_id={$this->appId}", [
                'headers' => [
                    'Authorization' => 'Basic ' . $this->restApiKey,
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $result,
            ];

        } catch (GuzzleException $e) {
            Log::error('OneSignal: Failed to cancel notification', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
