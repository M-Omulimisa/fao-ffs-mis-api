<?php

namespace App\Traits;

trait ApiResponser
{
    /**
     * Return a success response
     * 
     * @param string|array|null $dataOrMessage Response data or message
     * @param string|array|null $messageOrData Success message or data
     * @param int $code HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($dataOrMessage = null, $messageOrData = null, $code = 200)
    {
        $message = 'Success';
        $data = null;
        
        // Handle flexible parameter order for backward compatibility
        // Case 1: success($message, $data) - standard signature
        if (is_string($dataOrMessage) && (is_array($messageOrData) || is_object($messageOrData) || $messageOrData === null)) {
            $message = $dataOrMessage;
            $data = $messageOrData;
        }
        // Case 2: success($data, $message) - reversed (current usage)
        else if ((is_array($dataOrMessage) || is_object($dataOrMessage)) && (is_string($messageOrData) || $messageOrData === null)) {
            $data = $dataOrMessage;
            $message = $messageOrData ?? 'Success';
        }
        // Case 3: success($data) - only data provided
        else if ($dataOrMessage !== null && $messageOrData === null) {
            if (is_string($dataOrMessage)) {
                $message = $dataOrMessage;
            } else {
                $data = $dataOrMessage;
            }
        }
        
        $response = [
            'success' => true,
            'code' => 1,
            'status' => 1,
            'message' => $message,
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array|null $data Additional error data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error($message = "", $code = 400, $data = null)
    {
        // Log server errors with stack trace for debugging
        if ($code >= 500) {
            \Log::error("API Error [{$code}]: {$message}", [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'user_id' => auth()->id(),
                'trace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5))
                    ->map(fn($t) => ($t['file'] ?? '?') . ':' . ($t['line'] ?? '?') . ' ' . ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? ''))
                    ->implode("\n"),
            ]);
        }

        $response = [
            'success' => false,
            'code' => 0,
            'status' => 0,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        } else {
            $response['data'] = "";
        }

        return response()->json($response, $code);
    }
}
 