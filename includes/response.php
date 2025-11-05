<?php
// Helper JSON universal compatible con PHP 8.3
if (!function_exists('jsonResponse')) {
    function jsonResponse($data = [], $status = 200) {
        http_response_code((int)$status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
?>