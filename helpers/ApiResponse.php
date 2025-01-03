<?php
class ApiResponse {
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        return json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }
    
    public static function error($message = 'Error', $code = 400) {
        http_response_code($code);
        return json_encode([
            'status' => 'error',
            'message' => $message
        ]);
    }
} 