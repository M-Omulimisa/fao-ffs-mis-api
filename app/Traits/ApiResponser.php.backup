<?php

namespace App\Traits;

trait ApiResponser
{
    /**
     * Return a success response
     * 
     * @param string $message Success message
     * @param array|null $data Response data
     * @param int $code HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($message = "", $data = null, $code = 200)
    {
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
 