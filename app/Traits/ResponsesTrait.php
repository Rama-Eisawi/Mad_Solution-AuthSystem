<?php

namespace App\Traits;

trait ResponsesTrait
{
    public function sendSuccess($data, $message, $code = 200, $access_token, $refresh_token)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'token' => $access_token,
            'refresh_token' => $refresh_token
        ], $code);
    }
    public function sendFail($message = 'Request fails', $code = 422)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $code);
    }
}
