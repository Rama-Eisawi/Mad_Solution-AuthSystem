<?php
   namespace App\Traits;

   trait ResponsesTrait {
    public function sendSuccess($data, $message = 'Request successful', $code = 200,$token,$refresh_token)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'token'=>$token,
            'refresh_token'=>$refresh_token
        ], $code);
    }
    public function sendFail($message = 'Request fails', $code)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $code);
    }

     }

