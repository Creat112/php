<?php
class Response {
    public static function success($message = 'Success', $data = null) {
        $payload = ['status' => 'success', 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    public static function error($message = 'Error', $code = 400) {
        $payload = ['status' => 'error', 'message' => $message];
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($payload);
        exit;
    }
}
?>
