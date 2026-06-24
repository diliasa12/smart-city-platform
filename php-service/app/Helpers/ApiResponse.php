<?php

namespace App\Helpers;

class ApiResponse
{
    
    public static function success($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'meta' => [
                'status'  => 'success',
                'message' => $message,
                'code'    => $code,
            ],
            'data' => $data
        ], $code);
    }

    
    public static function error($message = 'Error', $code = 400, $errors = null)
    {
        return response()->json([
            'meta' => [
                'status'  => 'error',
                'message' => $message,
                'code'    => $code,
            ],
            'errors' => $errors
        ], $code);
    }
}